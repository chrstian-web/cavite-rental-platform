{{-- Makes every text input, select and textarea clearly visible (border, padding, readable placeholder, focus ring).
     Included once in each layout's <head>. Fields that set their own border colour (border-red-500, etc.) keep it. --}}
<style>
    input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="file"]):not([type="submit"]):not([type="button"]):not([type="range"]):not([type="color"]),
    select,
    textarea {
        border-width: 1px;
        border-style: solid;
        background-color: #fff;
        color: #0f172a;
        padding: 0.5rem 0.75rem;
        line-height: 1.25rem;
    }

    input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="file"]):not([type="submit"]):not([type="button"]):not([type="range"]):not([type="color"]):not([class*="border-"]),
    select:not([class*="border-"]),
    textarea:not([class*="border-"]) {
        border-color: #94a3b8;
    }

    input::placeholder,
    textarea::placeholder {
        color: #64748b;
        opacity: 1;
    }

    input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="file"]):not([type="submit"]):not([type="button"]):not([type="range"]):not([type="color"]):focus,
    select:focus,
    textarea:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
    }

    input:disabled,
    select:disabled,
    textarea:disabled,
    input[readonly],
    textarea[readonly] {
        background-color: #f1f5f9;
        color: #64748b;
        cursor: not-allowed;
    }

    input[type="file"] {
        font-size: 0.875rem;
        color: #334155;
    }
</style>
