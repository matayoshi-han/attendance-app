<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceRequest;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceCorrection;
use App\Models\RestCorrection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $monthParam = $request->query('month', now()->format('Y-m'));
        $userId = $request->input('user_id') ?? Auth::id();
        $targetUser = User::find($userId);

        $currentMonth = \Carbon\Carbon::parse($monthParam);

        // 変数名をBladeに合わせて $prevDate / $nextDate に変更
        $prevDate = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextDate = $currentMonth->copy()->addMonth()->format('Y-m');

        $attendances = Attendance::with('rests')
            ->where('user_id', $userId)
            ->where('work_date', 'like', $currentMonth->format('Y-m') . '%')
            ->orderBy('work_date', 'asc')
            ->get();

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

        // compactの中身をBladeの変数名に合わせる
        return view('attendance_list', compact(
            'attendances',
            'currentMonth',
            'prevDate',
            'nextDate',
            'userId',
            'targetUser'
        ));
    }


    public function adminAttendanceList(Request $request)
    {
        $dateParam = $request->query('date', Carbon::now()->toDateString());
        $currentDate = Carbon::parse($dateParam);

        $prevDate = $currentDate->copy()->subDay()->format('Y-m-d');
        $nextDate = $currentDate->copy()->addDay()->format('Y-m-d');

        // 「$attendances」という名前で全従業員分を取得（UserをEager Load）
        $attendances = Attendance::with('user')
            ->whereDate('work_date', $currentDate)
            ->paginate(10);

        return view('admin_attendance_list', [ // 全員用の一覧画面を指定
            'attendances' => $attendances,
            'currentDate' => $currentDate->toDateString(),
            'prevDate'    => $prevDate,
            'nextDate'    => $nextDate,
        ]);
    }



    public function adminStaffAttendanceList(Request $request, $user_id)
    {
        $targetUser = User::findOrFail($user_id);

        // 1. 対象の月を取得（デフォルトは今月）
        $monthParam = $request->query('month', now()->format('Y-m'));
        $currentMonth = \Carbon\Carbon::parse($monthParam);

        // 2. 前月・翌月の計算（変数名はBladeに合わせて prevDate / nextDate）
        $prevDate = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextDate = $currentMonth->copy()->addMonth()->format('Y-m');

        // 3. そのユーザーの指定された月の勤怠データをすべて取得
        $attendances = Attendance::with('rests')
            ->where('user_id', $user_id)
            ->whereYear('work_date', $currentMonth->year)
            ->whereMonth('work_date', $currentMonth->month)
            ->orderBy('work_date', 'asc')
            ->get();

        // 4. 各レコードの時間計算（休憩合計・勤務合計）
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

        // 5. 管理者用のビュー（または共通の attendance_list）を返す
        return view('attendance_list', [
            'targetUser'   => $targetUser,
            'attendances'  => $attendances,
            'currentMonth' => $currentMonth,
            'prevDate'     => $prevDate,
            'nextDate'     => $nextDate,
            'userId'       => $user_id,
        ]);
    }


    // CSVエクスポート処理
    public function export(Request $request)
    {
        $userId = $request->query('user_id');
        $month = $request->query('month');

        // 指定された月とユーザーのデータを取得
        $attendances = Attendance::with('rests')
            ->where('user_id', $userId)
            ->where('work_date', 'like', "$month%")
            ->orderBy('work_date', 'asc')
            ->get();

        return new StreamedResponse(function () use ($attendances) {
            $stream = fopen('php://output', 'w');

            // 文字化け対策（UTF-8 → Shift_JIS）
            stream_filter_append($stream, 'convert.iconv.utf-8/cp932//TRANSLIT');

            // ヘッダー
            fputcsv($stream, ['日付', '出勤', '退勤', '休憩時間', '合計勤務時間']);

            foreach ($attendances as $row) {
                // ここで indexList と同じ時間計算ロジックを入れる
                fputcsv($stream, [
                    $row->work_date,
                    $row->clock_in ? date('H:i', strtotime($row->clock_in)) : '',
                    $row->clock_out ? date('H:i', strtotime($row->clock_out)) : '',
                    // 合計休憩・勤務時間を計算して入れる
                ]);
            }
            fclose($stream);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=attendance_{$month}.csv",
        ]);
    }



    // 詳細画面の表示
    public function show($id)
    {
        $attendance = Attendance::with(['user', 'rests'])->findOrFail($id);

        // statusの制限を外し、最新の修正申請を取得する
        $correction = AttendanceCorrection::with('restCorrections')
            ->where('attendance_id', $id)
            ->latest() // 最新の申請を1件
            ->first();

        // 承認待ち(0) または 承認済み(1) のデータがあれば、それを表示用に使う
        if ($correction && ($correction->status == 0 || $correction->status == 1)) {
            $rests = $correction->restCorrections->map(function ($rc) {
                return (object)[
                    'break_start' => $rc->updated_break_start,
                    'break_end' => $rc->updated_break_end
                ];
            });
        } else {
            $rests = $attendance->rests;
        }

        if ($rests->isEmpty() || !is_null($rests->last()->break_end)) {
            $rests->push((object)['break_start' => null, 'break_end' => null]);
        }

        return view('attendance_detail', compact('attendance', 'rests', 'correction'));
    }


    // 修正申請
    public function storeCorrection(AttendanceRequest $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        // 管理者の場合
        if (Auth::user()->role === 'admin') {
            DB::transaction(function () use ($request, $attendance) {
                // 直接 attendances テーブルを更新
                $attendance->update([
                    'clock_in' => $request->clock_in ? $attendance->work_date . ' ' . $request->clock_in : null,
                    'clock_out' => $request->clock_out ? $attendance->work_date . ' ' . $request->clock_out : null,
                ]);

                // 休憩データも一度消して再作成（即時反映）
                $attendance->rests()->delete();
                if ($request->has('rests')) {
                    foreach ($request->rests as $restData) {
                        if (!empty($restData['start']) && !empty($restData['end'])) {
                            Rest::create([
                                'attendance_id' => $attendance->id,
                                'break_start' => $attendance->work_date . ' ' . $restData['start'],
                                'break_end' => $attendance->work_date . ' ' . $restData['end'],
                            ]);
                        }
                    }
                }
            });
            return redirect()->back()->with('success', '勤怠データを更新しました。');
        } else {
            DB::transaction(function () use ($request, $attendance) {
                // 1. 修正申請本体の保存
                $correction = AttendanceCorrection::create([
                    'attendance_id' => $attendance->id,
                    'user_id' => Auth::id(),
                    'updated_clock_in' => $request->clock_in ? $attendance->work_date . ' ' . $request->clock_in : null,
                    'updated_clock_out' => $request->clock_out ? $attendance->work_date . ' ' . $request->clock_out : null,
                    'remarks' => $request->remarks,
                    'status' => 0, // 承認待ち
                ]);

                // 2. 休憩時間の修正案も保存する
                if ($request->has('rests')) {
                    foreach ($request->rests as $restData) {
                        if (!empty($restData['start']) && !empty($restData['end'])) {
                            $correction->restCorrections()->create([
                                'updated_break_start' => $attendance->work_date . ' ' . $restData['start'],
                                'updated_break_end' => $attendance->work_date . ' ' . $restData['end'],
                            ]);
                        }
                    }
                }
            });
            return redirect()->back()->with('success', '修正申請を送信しました。');
        }
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
        $correction = AttendanceCorrection::with('restCorrections')->findOrFail($id);

        DB::transaction(function () use ($correction, $request) {
            // 詳細画面から送られる input (name="action") の値で分岐
            if ($request->action === 'approve') {
                // 1. 元の勤怠データを上書き
                $attendance = Attendance::findOrFail($correction->attendance_id);
                $attendance->update([
                    'clock_in' => $correction->updated_clock_in,
                    'clock_out' => $correction->updated_clock_out,
                ]);

                // 2. 元の休憩データを削除し、修正後の内容で再作成
                $attendance->rests()->delete();
                foreach ($correction->restCorrections as $rc) {
                    Rest::create([
                        'attendance_id' => $attendance->id,
                        'break_start' => $rc->updated_break_start,
                        'break_end' => $rc->updated_break_end,
                    ]);
                }

                $correction->update(['status' => 1]); // 承認済み
            } else {
                // 却下の場合
                $correction->update(['status' => 2]); // 却下
            }
        });

        return redirect()->route('correction.list')->with('success', '処理が完了しました。');
    }


    public function userList()
    {
        // 一般ユーザー（roleがuserのもの）を一覧で取得
        $users = \App\Models\User::where('role', 'user')->orderBy('id', 'asc')->get();

        return view('admin_user_list', compact('users'));
    }

    public function adminIndexList(Request $request)
    {
        // 1. 表示したい日付を取得（なければ今日）
        $displayDate = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();

        // 2. 前日・翌日の日付を計算して変数に入れる
        $prevDate = $displayDate->copy()->subDay()->format('Y-m-d');
        $nextDate = $displayDate->copy()->addDay()->format('Y-m-d');

        // 3. その日付に一致する勤怠データを取得
        $attendances = Attendance::with('user')
            ->whereDate('work_date', $displayDate) // 日付で絞り込み
            ->paginate(10);

        // 4. すべての変数を view に渡す
        return view('admin_attendance_list', [
            'attendances' => $attendances,
            'currentDate' => $displayDate->format('Y-m-d'), // 現在表示中の日
            'prevDate'    => $prevDate,                    // Bladeでエラーになっていた変数
            'nextDate'    => $nextDate,                    // 翌日用
        ]);
    }

    public function showEdit($id)
    {
        // 1. 現在の勤怠データと休憩データを取得
        $attendance = Attendance::with(['user', 'rests'])->findOrFail($id);

        // 2. この勤怠に関連する「修正申請」を取得（もしあれば）
        // 紐づく休憩の修正申請（restCorrections）も一緒に読み込む
        $correction = AttendanceCorrection::with('restCorrections')
            ->where('attendance_id', $id)
            ->first();

        // 3. Bladeでループに使用している $rests 変数を用意
        // 修正申請がある場合は申請中のデータを、なければ現在の休憩データを渡す
        $rests = $correction && $correction->restCorrections->isNotEmpty()
            ? $correction->restCorrections
            : $attendance->rests;

        return view('attendance_detail', [
            'attendance' => $attendance,
            'correction' => $correction,
            'rests'      => $rests,
        ]);
    }


    public function update(AttendanceRequest $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        DB::transaction(function () use ($request, $attendance) {
            // 勤怠データの更新
            $attendance->update([
                'clock_in' => $request->clock_in ? $attendance->work_date . ' ' . $request->clock_in : null,
                'clock_out' => $request->clock_out ? $attendance->work_date . ' ' . $request->clock_out : null,
            ]);

            // 休憩データの更新（既存のものを削除してから新しいものを追加）
            $attendance->rests()->delete();
            if ($request->has('rests')) {
                foreach ($request->rests as $restData) {
                    if (!empty($restData['start']) && !empty($restData['end'])) {
                        Rest::create([
                            'attendance_id' => $attendance->id,
                            'break_start' => $attendance->work_date . ' ' . $restData['start'],
                            'break_end' => $attendance->work_date . ' ' . $restData['end'],
                        ]);
                    }
                }
            }
        });

        return redirect()->route('admin.attendance.list')->with('success', '勤怠データを更新しました。');
    }
}
