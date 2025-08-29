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
    <script>
        const csrfToken = "{{ csrf_token() }}";
    </script>
    <header class="fixed top-0 left-0 w-full" style="z-index: 9999; height: 70px;">
        <div class="relative left-1/2 -translate-x-1/2 d-navbar d-bg-base-100 d-shadow-sm py-3 h-full" style="width: 95%">
            <div class="d-navbar-start">
                <div class="d-dropdown">
                    <div tabindex="0" role="button" class="d-btn d-btn-ghost lg:d-hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="d-h-5 d-w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16"/></svg>
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
            <div class="d-navbar-end">
                <a href="/login" class="d-btn d-btn-outline ">Login</a>
            </div>
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
    <section class="relative h-screen flex flex-col justify-between">
        <div id="bg-cta" class="x">
            <div class="bg-section-1 absolute top-0 left-0 w-full h-full"></div>
            <img src="{{ asset($tPath.'assets/img/cele-3.png') }}" alt="" class="absolute right-0"></img>
        </div>
        <div x-data="cardsList()" x-init="init()" class="self-center w-[90%] cards-container">
            {{-- <!-- Search (opsional) -->
            <div class="flex items-center gap-3 mb-3">
                <input x-model.debounce.300ms="query" type="text" placeholder="Cari venue…" class="input input-bordered w-full max-w-md">
                <button class="btn" @click="reset()">Reset</button>
            </div> --}}
            <ul class="cards grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-5 self-center w-[90%]">
                <!-- Skeleton loading -->
                <template x-if="loading && items.length === 0">
                    <template x-for="i in 6" :key="i">
                    <li class="card bg-base-100 w-96 shadow-sm animate-pulse">
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
                    <li class="card bg-base-100 w-96 shadow-sm">
                        <figure>
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
            </ul>
            <!-- Error -->
            <div class="alert alert-error mt-4" x-show="error" x-text="error"></div>
            <!-- Load more -->
            <div class="mt-6 flex justify-center">
                <button class="btn" 
                        @click="load()" 
                        :disabled="loading || !hasMore" 
                        x-text="hasMore ? (loading ? 'Loading…' : 'Load more') : 'No more data'">
                </button>
            </div>
        </div>
        <div class="sm:h-30 xl:h-40 relative flex flex-row justify-evenly overflow-y-visible">
            <img src="{{ asset($tPath.'assets/img/image-3.png') }}" alt="" class="h-70 self-end"></img>
            <div class="w-fit h-3/4 relative top-1/2 -translate-y-1/2 flex flex-col items-center text-white">
                <h2 class="">Add Your Loving Event</h2>
                <p class="">Lorem, ipsum dolor sit amet consectetur adipisicing elit.</p>
                <a href="/" class="" style="background-color: #F5167E">View all events</a>
            </div>
        </div>
    </section>
    <section class="">
        <div>
            <div></div>
        </div>
    </section>
    <footer>
        <div></div>
    </footer>
    {{-- @include('page.Components.preloader') --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js"></script>
    <script src="{{ asset($tPath.'js/RSA.js') }}"></script>
    <script src="{{ asset($tPath.'js/encryption.js') }}"></script>
    <script src="{{ asset($tPath.'js/home.js') }}"></script>
</body>
</html>