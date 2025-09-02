<div id="carouselComponent" class="sm:mb-4 flex flex-col items-center relative 3xsphone:left-1/2 md:left-0 3xsphone:-translate-x-1/2 md:-translate-x-0 whitespace-nowrap md:flex-1 w-10/12">
    <div class="relative w-10/12 flex items-center" @mouseenter="handleMainImage('enter')" @mouseleave="handleMainImage('leave')" @mousemove="handleMainImage('move')">
        <div class="refArr absolute z-10 left-0 flex justify-center items-center h-full 3xsphone:w-5 xl:w-10" style="boxShadow: '0 4px 30px rgba(0, 0, 0, 0.1)';">
            <span class="3xsphone:text-lg xsphone:text-xl sm:text-2xl md:text-3xl lg:text-4xl xl:text-5xl 2xl:text-5xl text-7xl text-primary_text dark:text-primary_dark cursor-pointer" @click="prevCarousel()"><</span>
        </div>
        <div class="card-loading items-loading absolute top-0 left-0 w-full h-full 3xsphone:rounded-md sm:rounded-lg md:rounded-xl" style="animation: 2.5s shine ease-in infinite; animation-delay: 0.25s;"/>
        <div class="inset-0">
            {{-- <img :src="props.images[0]+''" alt="" ref="mainImageRef" class="relative block object-contain 3xsphone:rounded-md sm:rounded-lg md:rounded-xl"> --}}
            <img src="{{ asset($tPath.'assets/img/party-1.png') }}" class="absolute block w-3/4 xl:w-[87%] -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2" alt="...">
        </div>
        <div class="absolute z-10">
            div
            <span class="3xsphone:text-lg xsphone:text-xl sm:text-2xl md:text-3xl lg:text-4xl xl:text-5xl 2xl:text-5xl text-7xl text-primary_text dark:text-primary_dark cursor-pointer" @click="prevCarousel()"><</span>
            <span class="3xsphone:text-lg xsphone:text-xl sm:text-2xl md:text-3xl lg:text-4xl xl:text-5xl 2xl:text-5xl text-7xl text-primary_text dark:text-primary_dark cursor-pointer" @click="nextCarousel()">></span>
            
        </div>
        <div class="refArr absolute z-10 right-0 flex justify-center items-center h-full 3xsphone:w-5 xl:w-10" style="boxShadow: '0 4px 30px rgba(0, 0, 0, 0.1)';">
    </div>
    <ul class="flex gap-1.5 mt-1 w-5/12 scrollable-container relative bg-primary dark:bg-transparent">
        <li ref="caItemLoadingRef" class="flex-shrink-0 relative">
            <div>
                <img :src="item+''" alt="" @click="updateImage(item, index)" class="pointer-events-auto relative block object-contain 3xsphone:rounded-sm md:rounded-md 3xsphone:border-3 md:border-3 border-transparent hover:border-primary dark:hover:border-primary_dark 3xsphone:max-h-40 xl:max-h-40" draggable="false">
            </div>
            <div class="card-loading items-loadinsg absolute top-0 left-0 z-10 w-full h-full 3xsphone:rounded-sm md:rounded-md" style="animation: 2.5s shine ease-in infinite; animation-delay: 0.25s;"/>
        </li>
        {{-- <template v-for="(item, index) in props.images" :key="index">
            <li ref="caItemLoadingRef" class="flex-shrink-0 relative">
                <div>
                    <img :src="item+''" alt="" @click="updateImage(item, index)" class="pointer-events-auto relative block object-contain 3xsphone:rounded-sm md:rounded-md 3xsphone:border-3 md:border-3 border-transparent hover:border-primary dark:hover:border-primary_dark 3xsphone:max-h-40 xl:max-h-40" draggable="false">
                </div>
                <div class="card-loading items-loadinsg absolute top-0 left-0 z-10 w-full h-full 3xsphone:rounded-sm md:rounded-md" style="animation: 2.5s shine ease-in infinite; animation-delay: 0.25s;"/>
            </li>
        </template> --}}
    </ul>
</div>