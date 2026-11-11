const base = {
    xmlns: 'http://www.w3.org/2000/svg',
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    strokeWidth: 2,
    strokeLinecap: 'round',
    strokeLinejoin: 'round',
    'aria-hidden': true,
};

export const IconLogin = ({ className = 'h-4 w-4' }) => (
    <svg {...base} className={className}>
        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
        <path d="M10 17l5-5-5-5" />
        <path d="M15 12H3" />
    </svg>
);

export const IconUserPlus = ({ className = 'h-4 w-4' }) => (
    <svg {...base} className={className}>
        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
        <circle cx="9" cy="7" r="4" />
        <path d="M19 8v6M22 11h-6" />
    </svg>
);

export const IconLogout = ({ className = 'h-4 w-4' }) => (
    <svg {...base} className={className}>
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
        <path d="M16 17l5-5-5-5" />
        <path d="M21 12H9" />
    </svg>
);

export const IconSearch = ({ className = 'h-4 w-4' }) => (
    <svg {...base} className={className}>
        <circle cx="11" cy="11" r="8" />
        <path d="M21 21l-4.3-4.3" />
    </svg>
);

export const IconDashboard = ({ className = 'h-4 w-4' }) => (
    <svg {...base} className={className}>
        <rect x="3" y="3" width="7" height="9" />
        <rect x="14" y="3" width="7" height="5" />
        <rect x="14" y="12" width="7" height="9" />
        <rect x="3" y="16" width="7" height="5" />
    </svg>
);

export const IconArrowLeft = ({ className = 'h-4 w-4' }) => (
    <svg {...base} className={className}>
        <path d="M19 12H5M12 19l-7-7 7-7" />
    </svg>
);

export const IconPlus = ({ className = 'h-4 w-4' }) => (
    <svg {...base} className={className}>
        <path d="M12 5v14M5 12h14" />
    </svg>
);

export const IconX = ({ className = 'h-5 w-5' }) => (
    <svg {...base} className={className}>
        <path d="M18 6L6 18M6 6l12 12" />
    </svg>
);

export const IconEye = ({ className = 'h-4 w-4' }) => (
    <svg {...base} className={className}>
        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" />
        <circle cx="12" cy="12" r="3" />
    </svg>
);

export const IconPencil = ({ className = 'h-4 w-4' }) => (
    <svg {...base} className={className}>
        <path d="M12 20h9" />
        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />
    </svg>
);

export const IconTrash = ({ className = 'h-4 w-4' }) => (
    <svg {...base} className={className}>
        <path d="M3 6h18" />
        <path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2" />
        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
        <path d="M10 11v6M14 11v6" />
    </svg>
);

export const IconDotsVertical = ({ className = 'h-5 w-5' }) => (
    <svg {...base} className={className}>
        <circle cx="12" cy="5" r="1" />
        <circle cx="12" cy="12" r="1" />
        <circle cx="12" cy="19" r="1" />
    </svg>
);

export const IconUpload = ({ className = 'h-4 w-4' }) => (
    <svg {...base} className={className}>
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
        <path d="M17 8l-5-5-5 5" />
        <path d="M12 3v12" />
    </svg>
);

export const IconGlobe = ({ className = 'h-4 w-4' }) => (
    <svg {...base} className={className}>
        <circle cx="12" cy="12" r="10" />
        <path d="M2 12h20" />
        <path d="M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20z" />
    </svg>
);

export const IconUsers = ({ className = 'h-4 w-4' }) => (
    <svg {...base} className={className}>
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
        <circle cx="9" cy="7" r="4" />
        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
    </svg>
);

export const IconFileText = ({ className = 'h-4 w-4' }) => (
    <svg {...base} className={className}>
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
        <path d="M14 2v6h6" />
        <path d="M16 13H8M16 17H8M10 9H8" />
    </svg>
);

export const IconUser = ({ className = 'h-4 w-4' }) => (
    <svg {...base} className={className}>
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
        <circle cx="12" cy="7" r="4" />
    </svg>
);

export const IconDownload = ({ className = 'h-4 w-4' }) => (
    <svg {...base} className={className}>
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
        <path d="M7 10l5 5 5-5" />
        <path d="M12 15V3" />
    </svg>
);

export const IconSparkles = ({ className = 'h-4 w-4' }) => (
    <svg {...base} className={className}>
        <path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z" />
        <path d="M19 15l.9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9z" />
    </svg>
);
