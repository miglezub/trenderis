@if(!Auth::user())
  <script>window.location = "/login";</script>
@else 
  <nav class="navbar navbar-expand-lg" id="top-navbar">
    <!-- <a class="navbar-brand" href="/dashboard">
          <x-application-logo class="block h-10 w-auto" />
    </a> -->
    <div id="sidebarCollapse" class="shrink"
        onclick="document.getElementById('sidebar').classList.toggle('shrink');
        document.getElementById('sidebarCollapse').classList.toggle('shrink');
        document.getElementsByTagName('main')[0].classList.toggle('shrink');
        document.getElementById('sidebarToggle').classList.toggle('fa-chevron-right');
        document.getElementById('sidebarToggle').classList.toggle('fa-chevron-left');">
        <i id="sidebarToggle" class="fas fa-lg fa-chevron-right"></i>
    </div>
    <div class="dropdown">
      <a class="dropdown-toggle" href="#" role="button" id="userDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        {{ Auth::user()->name }}
      </a>
      <div class="dropdown-menu" aria-labelledby="userDropdown">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <a class="dropdown-item" href="route('logout')" 
                onclick="event.preventDefault();this.closest('form').submit();">
                {{ __('auth.log_out') }}
            </a>
        </form>
      </div>
    </div>
    <!-- <ul class="navbar-nav">
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
        <i class="fas fa-chevron-down"></i>
      </button>
      <div class="collapse navbar-collapse" id="navbarNavDropdown">
        <ul class="navbar-nav">
          <li class="nav-item active">
            <a class="nav-link" href="#">Home <span class="sr-only">(current)</span></a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Features</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Pricing</a>
          </li>
        </ul>
      </div>
    </ul> -->
  </nav>
@endif