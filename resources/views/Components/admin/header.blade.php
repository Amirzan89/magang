<script>
    var errFotos = [];
    function imgError(err) {
        errFotos.push(err);
        var image = document.getElementById(err);
        if (image && image.src !== "{{ route('download.foto.default') }}") {
            image.src = "{{ route('download.foto.default') }}";
        }
    }
</script>
<header class="app-header">
    <nav class="navbar navbar-expand-lg navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item d-block d-xl-none">
                <a class="nav-link sidebartoggler nav-icon-hover" id="headerCollapse" href="javascript:void(0)">
                    <i class="ti ti-menu-2"></i>
                </a>
            </li>
        </ul>
        <div class="navbar-collapse justify-content-end px-0" id="navbarNav">
            <ul class="navbar-nav flex-row ms-auto align-items-center justify-content-end">
                <p>{{ $userAuth['nama_lengkap']}}</p>
                <li class="nav-item dropdown">
                    <a class="nav-link nav-icon-hover" href="javascript:void(0)" id="drop2" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <img src="{{ route('download.foto') }}" alt="Profile" width="35" height="35" id="top_bar" class="rounded-circle foto_admin" onerror="imgError('top_bar')">
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-animate-up" aria-labelledby="drop2">
                        <div class="message-body">
                            <a href="/profile" class="d-flex align-items-center gap-2 dropdown-item">
                                <i class="ti ti-user fs-6"></i>
                                <p class="mb-0 fs-3">My Profile</p>
                            </a>
                            <a href="#" class="btn btn-outline-primary mx-3 mt-2 d-block" onclick="logout()">Logout</a>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
</header>
<script>
    window.addEventListener('load', function() {
        var imgs = document.querySelectorAll('.foto_admin');
        imgs.forEach(function(image) {
            if (errFotos.includes(image.id)) {
                image.src = "{{ route('download.foto.default') }}";
            }
            if (image.complete && image.naturalWidth === 0) {
                image.src = "{{ route('download.foto.default') }}";
            }
        });
    });
</script>
<script src="{{ asset($tPath . '/js/page/logout.js') }}"></script>