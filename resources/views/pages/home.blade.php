<?php
$tPath = app()->environment('local') ? '' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Home | Uni Events</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <style>
        #bg-hero div{
            background: #ED4690;
            background: -webkit-linear-gradient(133deg, rgba(237, 70, 144, 1) 0%, rgba(85, 34, 204, 1) 100%);
            background: -moz-linear-gradient(133deg, rgba(237, 70, 144, 1) 0%, rgba(85, 34, 204, 1) 100%);
            background: linear-gradient(133deg, rgba(237, 70, 144, 1) 0%, rgba(85, 34, 204, 1) 100%);
            filter: progid:DXImageTransform.Microsoft.gradient(startColorstr="#ED4690", endColorstr="#5522CC", GradientType=0);
        }
        #bg-hero-carousel{
            background: RGBA(0, 0, 0, 0.8);
            background: -webkit-linear-gradient(45deg, rgba(0, 0, 0, 1) 13%, rgba(84, 32, 180, 1) 100%);
            background: -moz-linear-gradient(45deg, rgba(0, 0, 0, 1) 13%, rgba(84, 32, 180, 1) 100%);
            background: linear-gradient(45deg, rgba(0, 0, 0, 1) 13%, rgba(84, 32, 180, 1) 100%);
            filter: progid:DXImageTransform.Microsoft.gradient(startColorstr="#000000CC", endColorstr="#5420B400", GradientType=0);
        }
        section:nth-of-type(2) > div:not(.cards-container):not(#bg-cta){
            background: #8C44E4;
            background: -webkit-linear-gradient(90deg, rgba(140, 68, 228, 1) 0%, rgba(182, 83, 222, 1) 100%);
            background: -moz-linear-gradient(90deg, rgba(140, 68, 228, 1) 0%, rgba(182, 83, 222, 1) 100%);
            background: linear-gradient(90deg, rgba(140, 68, 228, 1) 0%, rgba(182, 83, 222, 1) 100%);
            filter: progid:DXImageTransform.Microsoft.gradient(startColorstr="#8C44E4", endColorstr="#B653DE", GradientType=1);
        }
        #bg-section1 div{
        }
    </style>
</head>
<body>
    @yield('content')
    <script>
        const csrfToken = "{{ csrf_token() }}";
    </script>
    {{-- <header class="fixed top-0 left-0 w-full" style="z-index: 9999; height: 70px;">
        <div class="relative left-1/2 -translate-x-1/2 d-navbar d-bg-base-100 d-shadow-sm py-3 h-full" style="width: 95%">
            <div class="d-navbar-start">
                <div class="d-dropdown">
                    <div tabindex="0" role="button" class="d-btn d-btn-ghost lg:d-hidden">
                    </div>
                    <ul tabindex="0" class="d-menu d-menu-sm d-dropdown-content d-bg-base-100 d-rounded-box d-z-1 d-mt-3 d-w-52 d-p-2 d-shadow text-white"">
                        <li><a href="/">Events</a></li>
                        <li><a href="/">ddd</a></li>
                        <li><a href="/">Item 3</a></li>
                        <li><a href="/" >Contact</a></li>
                    </ul>
                </div>
                <a class=""><img src="{{ asset($tPath.'assets/icon/logowhite.png') }}" alt="" class=""></img></a>
            </div>
            <div class="d-navbar-center d-hidden lg:d-flex text-white"">
                <ul class="d-menu d-menu-horizontal d-px-1">
                    <li><a href="/" >Events</a></li>
                    <li><a href="/" >About</a></li>
                    <li><a href="/" >Blog</a></li>
                    <li><a href="/" >Contact</a></li>
                </ul>
            </div>
            <div class="">
                <a href="/login" class="rounded-xl"><span>Login</span></a>
            </div>
        </div>
    </header> --}}
    <header class="fixed top-0 left-0 w-full" style="z-index: 9999; height: 70px;">
        <div class="dropdown-menu">
            <a class="/"><img src="{{ asset($tPath.'assets/icon/logowhite.png') }}" alt="" class=""></img></a>
        </div>
        <div>
            <div></div>
        </div>
    </header>
    <section class="relative h-screen">
        <div id="bg-hero">
            <div class="absolute top-0 left-0 w-full h-full"></div>
            <img src="{{ asset($tPath.'assets/img/party-1.png') }}" alt="" class="w-full"></img>
        </div>
        <div class="absolute left-1/2 -translate-x-1/2 -translate-y-1/2" style="top: 40%">
            <div class="bg-carousel"></div>
        </div>
    </section>
    <section class="relative min-h-screen h-fit flex flex-col justify-between border-black">
        <div id="bg-cta" class="x">
            <div class="bg-section-1 absolute top-0 left-0 w-full h-full"></div>
            <img src="{{ asset($tPath.'assets/img/cele-3.png') }}" alt="" class="absolute right-0"></img>
        </div>
        <div x-data="cardsList()" x-init="init()" class="relative w-[90%] flex-1 self-center cards-container">
            <div class="absolute -translate-y-1/2 w-full flex flex-row justify-between items-center">
                <div class="bg-white rounded-3xl">
                    <h2 class="text-[#242565] xl:text-3xl mt-4 mb-4 ml-6 mr-6">Upcoming Event</h2>
                </div>
                <div class="flex flex-row h-fit gap-5">
                    <div class="d-dropdown d-dropdown-bottom bg-white rounded-xl">
                        <div tabindex="0" role="button" class="mt-2 mb-2 ml-4 mr-4">WeekDays</div>
                        <ul tabindex="0" class="d-dropdown-content d-menu d-bg-base-100 d-rounded-box d-z-1 d-w-52 d-p-2 d-shadow-sm">
                            <li><a>Item 1</a></li>
                            <li><a>Item 2</a></li>
                        </ul>
                    </div>
                    <div class="d-dropdown d-dropdown-bottom bg-white rounded-xl">
                        <div tabindex="0" role="button" class="mt-2 mb-2 ml-4 mr-4">Popular</div>
                        <ul tabindex="0" class="d-dropdown-content d-menu d-bg-base-100 d-rounded-box d-z-1 d-w-52 d-p-2 d-shadow-sm">
                            <li><a>Item 1</a></li>
                            <li><a>Item 2</a></li>
                        </ul>
                    </div>
                    <div class="d-dropdown d-dropdown-bottom bg-white rounded-xl">
                        <div tabindex="0" role="button" class="mt-2 mb-2 ml-4 mr-4">Latest</div>
                        <ul tabindex="0" class="d-dropdown-content d-menu d-bg-base-100 d-rounded-box d-z-1 d-w-52 d-p-2 d-shadow-sm">
                            <li><a>Item 1</a></li>
                            <li><a>Item 2</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            {{-- <ul class="cards self-center bg-red-500" style="display: grid; grid-template-columns: repeat(3, 1fr); grid-template-rows: repeat(2, 1fr); grid-column-gap: 30px; grid-row-gap: 30px;">
                <!-- Skeleton loading -->
                <template x-if="loading && items.length === 0">
                    <template x-for="i in 6" :key="i">
                    <li class="card bg-base-100 shadow-sm animate-pulse">
                        <div class="h-48 bg-base-200"></div>
                        <div class="card-body">
                        <div class="h-5 bg-base-200 rounded w-2/3"></div>
                        <div class="h-4 bg-base-200 rounded w-full mt-2"></div>
                        <div class="h-10 bg-base-200 rounded mt-4"></div>
                        </div>
                    </li>
                    </template>
                </template> 
                <!-- Real items -->
                <template x-for="(item, idx) in filtered" :key="String(item.id)">
                    <li class="card bg-base-100 shadow-sm rounded-2xl">
                        <figure class="h-1/3">
                            <img :src="item.imageicon_1" :alt="item.title" loading="lazy" />
                        </figure>
                        <div class="card-body">
                            <h2 class="card-title" x-text="item.eventname"></h2>
                            <p x-text="item.startdate"></p>
                            <div class="card-actions justify-between items-center">
                                <span class="font-semibold" x-text="formatPrice(item.price)"></span>
                                <button class="btn btn-primary" @click="buy(item)">Buy Now</button>
                            </div>
                        </div>
                    </li>
                </template>
            </ul> --}}
            <a href="/events" class="relative left-1/2 -translate-x-1/2 mt-10 text-[#3D37F1] !border-[#3D37F1] rounded-2xl hover:bg-[#3D37F1] hover:text-white">See All Events</a>
        </div>
        <div class="sm:h-30 xl:h-40 flex flex-row mt-20 justify-evenly overflow-y-visible">
            <img src="{{ asset($tPath.'assets/img/image-3.png') }}" alt="" class="h-70 self-end"></img>
            <div class="w-fit h-3/4 relative top-1/2 -translate-y-1/2 flex flex-col items-center text-white">
                <h2 class="">Add Your Loving Event</h2>
                <p class="">Lorem, ipsum dolor sit amet consectetur adipisicing elit.</p>
                <a href="/" class="" style="background-color: #F5167E">View all events</a>
            </div>
        </div>
    </section>
    <section class="relative min-h-screen h-fit flex flex-col justify-between border-black border-4">
        <div id="bg-cta" class="x">
            <div class="bg-section-1 absolute top-0 left-0 w-full h-full"></div>
            <img src="{{ asset($tPath.'assets/img/cele-3.png') }}" alt="" class="absolute right-0"></img>
        </div>
    </section>
    <footer>
        <div></div>
    </footer>
    {{-- @include('page.Components.preloader') --}}
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js"></script>
    <script src="{{ asset($tPath.'js/RSA.js') }}"></script>
    <script src="{{ asset($tPath.'js/encryption.js') }}"></script>
    <script src="{{ asset($tPath.'js/home.js') }}"></script>
</body>
</html>