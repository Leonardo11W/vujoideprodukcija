<!-- <a href="{{route('index')}}" class="navbar-brand">
    <img src="{{ asset('img/logo/logo.png') }}" alt="Logo" class="logo img-fluid">
</a> -->


<a class="navbar-brand text-primary" href="{{ route('index') }}"> 
        <div class="logo-main">
            <div class="logo-mini d-none">
                <img src="{{ asset(setting('mini_logo')) }}" height="30" alt="{{ app_name() }}">
            </div>
            <div class="logo-normal">
                <img src="{{ asset(setting('logo')) }}" height="30" alt="{{ app_name() }}">
            </div>
            <div class="logo-dark">
                <img src="{{ asset(setting('dark_logo')) }}" height="30" alt="{{ app_name() }}">
            </div>
        </div>
    </a>