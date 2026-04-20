<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Rest;
use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceCorrection;
use App\Models\RestCorrection;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    // 表示処理
    public function index()
    {
        $today = now()->toDateString();
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('work_date', $today)
            ->first();

        $status = '勤務外';

        if ($attendance) {
            if ($attendance->clock_in && !$attendance->clock_out) {
                $latestRest = Rest::where('attendance_id', $attendance->id)->latest()->first();

                if ($latestRest && !$latestRest->break_end) {
                    $status = '休憩中';
                } else {
                    $status = '出勤中';
                }
            } else {
                $status = '退勤済';
            }
        }

        return view('attendance', compact('status'));
    }

    // 出勤処理
    public function start()
    {
        $today = now()->toDateString();
        $exists = Attendance::where('user_id', Auth::id())
            ->where('work_date', $today)
            ->exists();

        if (!$exists) {
            Attendance::create([
                'user_id' => Auth::id(),
                'work_date' => $today,
                'clock_in' => now(),
            ]);
        }

        return back();
    }

    // 退勤処理
    public function end()
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('work_date', now()->toDateString())
            ->first();

        if ($attendance && !$attendance->clock_out) {
            $attendance->update(['clock_out' => now()]);
        }

        return back();
    }

    // 休憩開始
    public function breakStart()
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('work_date', now()->toDateString())
            ->first();

        if ($attendance && !$attendance->clock_out) {
            Rest::create([
                'attendance_id' => $attendance->id,
                'break_start' => now(),
            ]);
        }

        return back();
    }

    // 休憩終了
    public function breakEnd()
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('work_date', now()->toDateString())
            ->first();

        if ($attendance) {
            $rest = Rest::where('attendance_id', $attendance->id)
                ->whereNull('break_end')
                ->first();

            if ($rest) {
                $rest->update(['break_end' => now()]);
            }
        }

        return back();
    }

    public function indexList(Request $request)
    {
        // クエリパラメータから月を取得、なければ今月
        $monthParam = $request->query('month', now()->format('Y-m'));

        // Carbonインスタンスを作成
        $currentMonth = \Carbon\Carbon::parse($monthParam);
        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        $attendances = Attendance::with('rests')
            ->where('user_id', Auth::id())
            ->where('work_date', 'like', $currentMonth->format('Y-m') . '%')
            ->orderBy('work_date', 'asc')
            ->get();

        // 時間計算ロジック
        foreach ($attendances as $attendance) {
            $totalRestSeconds = 0;
            foreach ($attendance->rests as $rest) {
                if ($rest->break_start && $rest->break_end) {
                    $totalRestSeconds += strtotime($rest->break_end) - strtotime($rest->break_start);
                }
            }
            $attendance->total_rest_time = gmdate('H:i:s', $totalRestSeconds);

            if ($attendance->clock_in && $attendance->clock_out) {
                $staySeconds = strtotime($attendance->clock_out) - strtotime($attendance->clock_in);
                $workSeconds = $staySeconds - $totalRestSeconds;
                $attendance->total_work_time = gmdate('H:i:s', max(0, $workSeconds));
            } else {
                $attendance->total_work_time = '00:00:00';
            }
        }

        return view('attendance_list', compact('attendances', 'currentMonth', 'prevMonth', 'nextMonth'));
    }

    // 詳細画面の表示
    public function show($id)
    {
        $attendance = Attendance::with(['user', 'rests'])->findOrFail($id);

        // 承認待ちの修正申請があるか確認
        $correction = AttendanceCorrection::with('restCorrections')
            ->where('attendance_id', $id)
            ->where('status', 0) // 承認待ち
            ->first();

        // 修正申請があればそれを、なければ現在の勤怠データを表示用にセット
        $displayData = $correction ?: $attendance;

        // 休憩データの整理
        // 承認待ちがあれば修正後の休憩を、なければ今の休憩を取得
        if ($correction) {
            $rests = $correction->restCorrections->map(function ($rc) {
                return (object)[
                    'break_start' => $rc->updated_break_start,
                    'break_end' => $rc->updated_break_end
                ];
            });
        } else {
            $rests = $attendance->rests;
        }

        // 新規休憩入力用の空枠を1つ追加
        if ($rests->isEmpty() || !is_null($rests->last()->break_end)) {
            $rests->push((object)['break_start' => null, 'break_end' => null]);
        }

        return view('attendance_detail', compact('attendance', 'rests', 'correction'));
    }

    // 修正申請の保存
    public function storeCorrection(Request $request, $id)
    {
        // バリデーション
        $request->validate([
            'remarks' => 'required|string',
        ]);

        DB::transaction(function () use ($request, $id) {
            $attendance = Attendance::findOrFail($id);

            // 1. 修正申請メインテーブルの保存
            $correction = AttendanceCorrection::create([
                'attendance_id' => $attendance->id,
                'user_id' => Auth::id(),
                'updated_clock_in' => $request->clock_in ? $attendance->work_date . ' ' . $request->clock_in : null,
                'updated_clock_out' => $request->clock_out ? $attendance->work_date . ' ' . $request->clock_out : null,
                'remarks' => $request->remarks,
                'status' => 0, // 承認待ち
            ]);

            // 2. 休憩修正データの保存
            if ($request->has('rests')) {
                foreach ($request->rests as $restData) {
                    // 開始・終了どちらか入力があれば保存
                    if ($restData['start'] || $restData['end']) {
                        RestCorrection::create([
                            'attendance_correction_id' => $correction->id,
                            'updated_break_start' => $restData['start'] ? $attendance->work_date . ' ' . $restData['start'] : null,
                            'updated_break_end' => $restData['end'] ? $attendance->work_date . ' ' . $restData['end'] : null,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('attendance.list')->with('success', '修正申請を送信しました。');
    }

    public function correctionList(Request $request)
    {
        // タブ切り替え（承認待ち or 承認済み）
        $tab = $request->query('tab', 'pending');

        $query = AttendanceCorrection::with(['user', 'attendance']);

        if ($tab === 'pending') {
            $query->where('status', 0); // 承認待ち
        } else {
            $query->whereIn('status', [1, 2]); // 承認済み・却下
        }

        $corrections = $query->orderBy('created_at', 'desc')->get();

        return view('correction_list', compact('corrections', 'tab'));
    }

    // 修正申請の承認・却下（管理者用）
    public function approveCorrection(Request $request, $id)
    {
        // $id は attendance_corrections の ID
        $correction = AttendanceCorrection::with('restCorrections')->findOrFail($id);

        DB::transaction(function () use ($correction, $request) {
            if ($request->action === 'approve') {
                // 1. 元の勤怠データを上書き
                $attendance = Attendance::findOrFail($correction->attendance_id);
                $attendance->update([
                    'clock_in' => $correction->updated_clock_in,
                    'clock_out' => $correction->updated_clock_out,
                ]);

                // 2. 元の休憩データを一度消して、修正後の内容で再作成
                Rest::where('attendance_id', $attendance->id)->delete();
                foreach ($correction->restCorrections as $rc) {
                    Rest::create([
                        'attendance_id' => $attendance->id,
                        'break_start' => $rc->updated_break_start,
                        'break_end' => $rc->updated_break_end,
                    ]);
                }

                $correction->update(['status' => 1]); // 承認済み
            } else {
                $correction->update(['status' => 2]); // 却下
            }
        });

        return redirect()->route('correction.list')->with('success', '処理が完了しました。');
    }
}
