<aside class="left-sidebar">
    <!-- Sidebar scroll-->
    <div>
        <div class="brand-logo d-flex align-items-center justify-content-between">
            <a href="/dashboard" class="text-nowrap logo-img" style="display: flex; gap: 10px; align-items:center">
                <img src="{{ asset($tPath.'img/icon/logo.png') }}" alt="" style="width: 100px; height:50px;"></img>
                <span class="hide-menu" style="color:black; text-decoration: none; font-size:27px; font-weight:600;">EduAksi</span>
            </a>
            <div class="close-btn d-xl-none d-block sidebartoggler cursor-pointer" id="sidebarCollapse">
                <i class="ti ti-x fs-8"></i>
            </div>
        </div>
        <!-- Sidebar navigation-->
        <nav class="sidebar-nav scroll-sidebar" data-simplebar="" style="margin-top: 15px">
            <ul id="sidebarnav" style="display:flex; flex-direction: column; gap: 2px;">
                <li class="sidebar-item {{ $nav == 'dashboard' ? 'selected' : ''}}">
                    <a class="sidebar-link {{ $nav == 'dashboard' ? 'active' : ''}}" href="/dashboard"
                        aria-expanded="false">
                        <img src="{{ asset($tPath.'img/icon/sidebar/dashboard.svg') }}" alt="" width="30" height="30" style="display: {{ $nav == 'dashboard' ? 'block' : 'none'}}" class="white">
                        <img src="{{ asset($tPath.'img/icon/sidebar/dashboard_dark.svg') }}" alt="" width="30" height="30" style="display: {{ $nav == 'dashboard' ? 'none' : 'block'}}" class="dark">
                        <span class="hide-menu">Dashboard</span>
                    </a>
                </li>

                @if($userAuth['role'] == 'super admin' || $userAuth['role'] == 'admin disi')
                <li class="sidebar-item {{ $nav == 'disi' ? 'selected' : ''}}">
                    <a class="sidebar-link {{ $nav == 'disi' ? 'active' : ''}}" href="/disi" aria-expanded="false">
                        <img src="{{ asset($tPath.'img/icon/sidebar/disi.svg') }}" alt="" width="30" height="30" style="display: {{ $nav == 'disi' ? 'block' : 'none'}}" class="white">
                        <img src="{{ asset($tPath.'img/icon/sidebar/disi_dark.svg') }}" alt="" width="30" height="30" style="display: {{ $nav == 'disi' ? 'none' : 'block'}}" class="dark">
                        <span class="hide-menu">Kelola Disi</span>
                    </a>
                </li>
                @endif

                @if($userAuth['role'] == 'super admin' || $userAuth['role'] == 'admin emotal')
                <li class="sidebar-item {{ $nav == 'emotal' ? 'selected' : ''}}">
                    <a class="sidebar-link {{ $nav == 'emotal' ? 'active' : ''}}" href="/emotal" aria-expanded="false">
                        <img src="{{ asset($tPath.'img/icon/sidebar/emotal.svg') }}" alt="" width="30" height="30" style="display: {{ $nav == 'emotal' ? 'block' : 'none'}}" class="white">
                        <img src="{{ asset($tPath.'img/icon/sidebar/emotal_dark.svg') }}" alt="" width="30" height="30" style="display: {{ $nav == 'emotal' ? 'none' : 'block'}}" class="dark">
                        <span class="hide-menu">Kelola Emotal</span>
                    </a>
                </li>
                @endif

                @if($userAuth['role'] == 'super admin' || $userAuth['role'] == 'admin nutrisi')
                <li class="sidebar-item {{ $nav == 'nutrisi' ? 'selected' : ''}}">
                    <a class="sidebar-link {{ $nav == 'nutrisi' ? 'active' : ''}}" href="/nutrisi"
                        aria-expanded="false">
                        <img src="{{ asset($tPath.'img/icon/sidebar/nutrisi.svg') }}" alt="" width="30" height="30" style="display: {{ $nav == 'nutrisi' ? 'block' : 'none'}}" class="white">
                        <img src="{{ asset($tPath.'img/icon/sidebar/nutrisi_dark.svg') }}" alt="" width="30" height="30" style="display: {{ $nav == 'nutrisi' ? 'none' : 'block'}}" class="dark">
                        <span class="hide-menu">Kelola Nutrisi</span>
                    </a>
                </li>
                @endif

                @if($userAuth['role'] == 'super admin' || $userAuth['role'] == 'admin pengasuhan')
                <li class="sidebar-item {{ $nav == 'pengasuhan' ? 'selected' : ''}}">
                    <a class="sidebar-link {{ $nav == 'pengasuhan' ? 'active' : ''}}" href="/pengasuhan"
                        aria-expanded="false">
                        <img src="{{ asset($tPath.'img/icon/sidebar/pengasuhan.svg') }}" alt="" width="30" height="30" style="display: {{ $nav == 'pengasuhan' ? 'block' : 'none'}}" class="white">
                        <img src="{{ asset($tPath.'img/icon/sidebar/pengasuhan_dark.svg') }}" alt="" width="30" height="30" style="display: {{ $nav == 'pengasuhan' ? 'none' : 'block'}}" class="dark">
                        <span class="hide-menu">Kelola Pengasuhan</span>
                    </a>
                </li>
                @endif

                <li class="sidebar-item {{ $nav == 'konsultasi' ? 'selected' : ''}}">
                    <a class="sidebar-link {{ $nav == 'konsultasi' ? 'active' : ''}}" href="/konsultasi"
                        aria-expanded="false">
                        <img src="{{ asset($tPath.'img/icon/sidebar/konsultasi.svg') }}" alt="" width="30" height="30" style="display: {{ $nav == 'konsultasi' ? 'block' : 'none'}}" class="white">
                        <img src="{{ asset($tPath.'img/icon/sidebar/konsultasi_dark.svg') }}" alt="" width="30" height="30" style="display: {{ $nav == 'konsultasi' ? 'none' : 'block'}}" class="dark">
                        <span class="hide-menu">Kelola Konsultasi</span>
                    </a>
                </li>

                <li class="sidebar-item {{ $nav == 'artikel' ? 'selected' : ''}}">
                    <a class="sidebar-link {{ $nav == 'artikel' ? 'active' : ''}}" href="/article"
                        aria-expanded="false">
                        <img src="{{ asset($tPath.'img/icon/sidebar/artikel.svg') }}" alt="" width="30" height="30" style="display: {{ $nav == 'artikel' ? 'block' : 'none'}}" class="white">
                        <img src="{{ asset($tPath.'img/icon/sidebar/artikel_dark.svg') }}" alt="" width="30" height="30" style="display: {{ $nav == 'artikel' ? 'none' : 'block'}}" class="dark">
                        <span class="hide-menu">Kelola Artikel</span>
                    </a>
                </li>

                <li class="sidebar-item {{ $nav == 'event' ? 'selected' : ''}}">
                    <a class="sidebar-link {{ $nav == 'event' ? 'active' : ''}}" href="/acara"
                        aria-expanded="false">
                        <img src="{{ asset($tPath.'img/icon/sidebar/event.svg') }}" alt="" width="30" height="30" style="display: {{ $nav == 'event' ? 'block' : 'none'}}" class="white">
                        <img src="{{ asset($tPath.'img/icon/sidebar/event_dark.svg') }}" alt="" width="30" height="30" style="display: {{ $nav == 'event' ? 'none' : 'block'}}" class="dark">
                        <span class="hide-menu">Kelola Acara</span>
                    </a>
                </li>

                @if($userAuth['role'] == 'super admin')
                <li class="sidebar-item {{ $nav == 'riwayat' ? 'selected' : ''}}">
                    <a class="sidebar-link {{ $nav == 'riwayat' ? 'active' : ''}}" href="/riwayat"
                        aria-expanded="false">
                        <img src="{{ asset($tPath.'img/icon/sidebar/riwayat.svg') }}" alt="" width="30" height="30" style="display: {{ $nav == 'riwayat' ? 'block' : 'none'}}" class="white">
                        <img src="{{ asset($tPath.'img/icon/sidebar/riwayat_dark.svg') }}" alt="" width="30" height="30" style="display: {{ $nav == 'riwayat' ? 'none' : 'block'}}" class="dark">
                        <span class="hide-menu">Kelola Riwayat</span>
                    </a>
                </li>
                @endif

                @if($userAuth['role'] == 'super admin')
                <li class="sidebar-item {{ $nav == 'admin' ? 'selected' : ''}}">
                    <a class="sidebar-link {{ $nav == 'admin' ? 'active' : ''}}"" href=" /admin" aria-expanded="false">
                        <img src="{{ asset($tPath.'img/icon/sidebar/admin.svg') }}" alt="" width="30" height="30" style="display: {{ $nav == 'admin' ? 'block' : 'none'}}" class="white">
                        <img src="{{ asset($tPath.'img/icon/sidebar/admin_dark.svg') }}" alt="" width="30" height="30" style="display: {{ $nav == 'admin' ? 'none' : 'block'}}" class="dark">
                        <span class="hide-menu">Kelola Admin</span>
                    </a>
                </li>
                @endif
            </ul>
        </nav>
        <!-- End Sidebar navigation -->
    </div>
    <!-- End Sidebar scroll-->
</aside>
<script>
    const sidebarItems = document.querySelectorAll('.sidebar-item');
    sidebarItems.forEach(function(item){
        if(!item.classList.contains('selected')){
            item.addEventListener('click', function(){
                item.querySelector('.dark').style.display = 'none';
                item.querySelector('.white').style.display = 'block';
                sidebarItems.forEach(function(itemActive){
                    if(itemActive.classList.contains('selected')){
                        itemActive.querySelector('.dark').style.display = 'block';
                        itemActive.querySelector('.white').style.display = 'none';
                        itemActive.classList.remove('selected');
                    }
                });
                item.classList.add('selected');
            });
        }
    });
</script>