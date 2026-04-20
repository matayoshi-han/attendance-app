<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoachTech Attendance</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <div class="header-utilities">
                <a class="header__logo" href="/attendance">
                    <img src="{{ asset('images/COACHTECHヘッダーロゴ.png') }}" alt="ロゴ">
                </a>
                @if (!Route::is(['login', 'register', 'verification.notice']))
                <nav>
                    <ul class="header-nav">
                        @if (Auth::check())
                        @if (isset($status) && $status === '退勤済')
                        <li class="header-nav__item">
                            <a class="header-nav__link" href="/attendance/list">今月の出勤一覧</a>
                        </li>
                        <li class="header-nav__item">
                            <a class="header-nav__link" href="/stamp_correction_request/list">申請一覧</a>
                        </li>
                        @else
                        <li class="header-nav__item">
                            <a class="header-nav__link" href="/attendance">勤怠</a>
                        </li>
                        <li class="header-nav__item">
                            <a class="header-nav__link" href="/attendance/list">勤怠一覧</a>
                        </li>
                        <li class="header-nav__item">
                            <a class="header-nav__link" href="/stamp_correction_request/list">申請</a>
                        </li>
                        @endif

                        <li class="header-nav__item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="header-nav__button">ログアウト</button>
                            </form>
                        </li>
                        @endif
                    </ul>
                </nav>
                @else
                <div class="header__search-spacer" style="flex: 1;"></div>
                @endif
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>
</body>

</html>