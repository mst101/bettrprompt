<template>
    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
        <!-- AI Buddy logo - friendly robot face -->
        <g>
            <!-- Head -->
            <rect
                x="20"
                y="25"
                width="60"
                height="60"
                rx="12"
                fill="currentColor"
                opacity="0.9"
            />

            <!-- Antenna -->
            <line
                x1="50"
                y1="25"
                x2="50"
                y2="15"
                stroke="currentColor"
                stroke-width="3"
                stroke-linecap="round"
            />
            <circle cx="50" cy="12" r="4" fill="currentColor" />

            <!-- Eyes -->
            <circle cx="37" cy="45" r="6" fill="white" />
            <circle cx="63" cy="45" r="6" fill="white" />
            <circle cx="37" cy="45" r="3" fill="currentColor" />
            <circle cx="63" cy="45" r="3" fill="currentColor" />

            <!-- Smile -->
            <path
                d="M 35 62 Q 50 72 65 62"
                stroke="white"
                stroke-width="3"
                fill="none"
                stroke-linecap="round"
            />
        </g>
    </svg>
</template>
