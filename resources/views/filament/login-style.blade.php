@php
    $brandColor = 'rgb(20, 184, 166)'; // Teal-500
    $brandColorLight = 'rgb(204, 251, 241)'; // Teal-100
@endphp

<style>
    /* Override Filament login page */
    .fi-simple-layout {
        background: linear-gradient(135deg, #0d9488 0%, #0ea5e9 50%, #10b981 100%) !important;
        min-height: 100vh;
    }

    .fi-simple-main-ctn {
        backdrop-filter: blur(20px);
    }

    .fi-simple-main {
        background: rgba(255, 255, 255, 0.95) !important;
        border-radius: 1.5rem !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25),
                    0 0 0 1px rgba(255, 255, 255, 0.1) !important;
        padding: 2.5rem !important;
    }

    /* Brand logo area */
    .fi-logo {
        margin-bottom: 0.5rem;
    }

    .fi-logo img {
        height: 4rem !important;
        width: auto !important;
    }

    /* Heading */
    .fi-simple-header-heading {
        font-size: 1.5rem !important;
        font-weight: 700 !important;
        color: #0f172a !important;
    }

    .fi-simple-header-subheading {
        color: #64748b !important;
    }

    /* Form inputs */
    .fi-simple-main .fi-input-wrp {
        border-radius: 0.75rem !important;
        border-color: #e2e8f0 !important;
        transition: all 0.2s ease !important;
    }

    .fi-simple-main .fi-input-wrp:focus-within {
        border-color: {{ $brandColor }} !important;
        box-shadow: 0 0 0 3px {{ $brandColorLight }} !important;
    }

    /* Submit button */
    .fi-simple-main .fi-btn-primary {
        border-radius: 0.75rem !important;
        padding-top: 0.75rem !important;
        padding-bottom: 0.75rem !important;
        font-weight: 600 !important;
        letter-spacing: 0.025em !important;
        transition: all 0.2s ease !important;
    }

    .fi-simple-main .fi-btn-primary:hover {
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 12px rgba(20, 184, 166, 0.4) !important;
    }

    /* Footer text */
    .fi-simple-footer {
        color: rgba(255, 255, 255, 0.7) !important;
    }

    .fi-simple-footer a {
        color: rgba(255, 255, 255, 0.9) !important;
    }

    /* Floating shapes decoration */
    .fi-simple-layout::before,
    .fi-simple-layout::after {
        content: '';
        position: fixed;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.05);
        pointer-events: none;
    }

    .fi-simple-layout::before {
        width: 400px;
        height: 400px;
        top: -100px;
        right: -100px;
    }

    .fi-simple-layout::after {
        width: 300px;
        height: 300px;
        bottom: -80px;
        left: -80px;
    }
</style>
