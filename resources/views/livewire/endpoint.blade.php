<div>
    <div class="flex flex-row mb-2 gap-5 p-2 min-h-full">
        <div id="endpoint" class=" bg-white rounded-lg shadow-md overflow-y-auto basis-1/4 p-5 ">
            <p class="text-black font-bold my-2 text-xl">Endpoint Collection (REST API)</p>
            <ul class="my-5">
                @forelse ($endpointList as $endpoint)
                    <li class="relative my-4">
                        <button
                            class="inline-flex items-center justify-between w-full text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200"
                            @click="togglePagesMenu" aria-haspopup="true">
                            <span class="inline-flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" height="1em" fill="currentColor"
                                    stroke="currentColor"
                                    viewBox="0 0 512 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. -->
                                    <path
                                        d="M251.7 127.6l0 0c10.5 10.5 24.7 16.4 39.6 16.4H448c8.8 0 16 7.2 16 16v32H48V96c0-8.8 7.2-16 16-16H197.5c4.2 0 8.3 1.7 11.3 4.7l33.9-33.9L208.8 84.7l42.9 42.9zM48 240H464V416c0 8.8-7.2 16-16 16H64c-8.8 0-16-7.2-16-16V240zM285.7 93.7L242.7 50.7c-12-12-28.3-18.7-45.3-18.7H64C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V160c0-35.3-28.7-64-64-64H291.3c-2.1 0-4.2-.8-5.7-2.3z" />
                                </svg>
                                <span class="ml-4">{{ $endpoint['endpoint_parent'] }}</span>
                            </span>
                            <svg class="w-4 h-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </button>
                        <template x-if="isPagesMenuOpen">
                            <ul x-transition:enter="transition-all ease-in-out duration-300"
                                x-transition:enter-start="opacity-25 max-h-0"
                                x-transition:enter-end="opacity-100 max-h-xl"
                                x-transition:leave="transition-all ease-in-out duration-300"
                                x-transition:leave-start="opacity-100 max-h-xl"
                                x-transition:leave-end="opacity-0 max-h-0"
                                class="p-2  space-y-2 overflow-hidden text-sm font-medium text-gray-500 rounded-md  bg-gray-50 dark:text-gray-400 dark:bg-gray-900"
                                aria-label="submenu">
                                @foreach ($endpoint['endpoints'] as $item)
                                    <li
                                        class="px-2  transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200 flex flex-row gap-4">
                                        <span class="text-xs text-green-600 ">{{ $item['endpoint_action'] }}</span>
                                        <a class="w-full" href="#"
                                            wire:click="onGetEndpoint('{{ $endpoint['endpoint_parent'] }}',{{ json_encode($item) }})">
                                            {{ $item['endpoint_name'] }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </template>
                    </li>
                @empty
                @endforelse
            </ul>
        </div>
        <div class="basis-full">
            <div id="request" class="relative bg-white rounded-lg shadow-md p-5 mb-10 overflow-y-auto ">
                <p class="text-xl text-black font-bold mb-3">Example Request</p>
                <p class="text-xl text-black font-medium mb-1">{{ $selectedTitle ?? 'Title' }} </p>
                <p class="text-md text-black font-medium mb-1">
                    {{ $selectedEndpoint['endpoint_description'] ?? 'Description ' }}</p>
                <hr class="border border-grey my-2">
                <p class="text-black font-bold mb-3 text-md">
                    <span
                        class="text-green-600 font-semibold mr-2">{{ $selectedEndpoint['endpoint_action'] ?? 'Action' }}</span>
                    {{ $selectedEndpoint['endpoint_name'] ?? 'Endpoint Name' }}
                </p>
                <div class="relative mb-3">
                    <input type="search"
                        class="block w-full p-4 text-sm text-black shadow-md focus:ring-1 rounded-md bg-gray-200 focus:shadow-outline-primary focus:outline-none"
                        value="{{ $endpointURL }}" disabled>
                    <span
                        class="text-white absolute right-2.5 bottom-2.5 hover:opacity-90 hover:bg-white cursor-pointer font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-primary dark:focus:ring-blue-800"
                        title="Copy" @click.prevent="copyTextToClipboard('{{ $endpointURL }}')">
                        <svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 448 512">
                            <path
                                d="M384 336H192c-8.8 0-16-7.2-16-16V64c0-8.8 7.2-16 16-16l140.1 0L400 115.9V320c0 8.8-7.2 16-16 16zM192 384H384c35.3 0 64-28.7 64-64V115.9c0-12.7-5.1-24.9-14.1-33.9L366.1 14.1c-9-9-21.2-14.1-33.9-14.1H192c-35.3 0-64 28.7-64 64V320c0 35.3 28.7 64 64 64zM64 128c-35.3 0-64 28.7-64 64V448c0 35.3 28.7 64 64 64H256c35.3 0 64-28.7 64-64V416H272v32c0 8.8-7.2 16-16 16H64c-8.8 0-16-7.2-16-16V192c0-8.8 7.2-16 16-16H96V128H64z" />
                        </svg>
                    </span>
                    </span>
                </div>
                {{--  <p class="text-md text-black font-medium ">Header</p>
                <textarea id="message" rows="3" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-200  rounded-lg shadow-md mb-3" disabled>
                    {{ $selectedEndpoint['endpoint_body'] ?? 'No body' }}
                </textarea> --}}
                <p class="text-md text-black font-medium ">Body</p>
                <textarea id="message" rows="5"
                    class="block p-2.5 w-full text-sm text-gray-900 bg-gray-200  rounded-lg shadow-md mb-3" disabled>
                    {{ $selectedEndpoint['endpoint_body'] ?? 'No body' }}
                </textarea>
                {{-- <button class="bg-primary text-white py-1 px-4 rounded-md absolute bottom-4 right-5 hover:bg-red-800" wire:click="onTest">Test</button> --}}
            </div>
            <div id="result" class="bg-white rounded-lg shadow-md p-5  ">
                <p class="text-xl text-black font-bold mb-2">Example Response</p>
                <div class="bg-gray-200 rounded-lg shadow-md">
                    <pre>
                        {{ $selectedEndpoint['endpoint_response'] ?? '' }}
                    </pre>
                </div>
            </div>
        </div>
       
    </div>

</div>
