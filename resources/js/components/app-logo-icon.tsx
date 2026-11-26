import type { SVGAttributes } from 'react';

/** Un tique con el borde de abajo cortado en zigzag y tres líneas impresas. */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 32 40" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path fillRule="evenodd" d="M2 0h28v36l-3.5 4-3.5-4-3.5 4-3.5-4-3.5 4-3.5-4-3.5 4L2 36V0Zm6 9h16v3H8V9Zm0 7h16v3H8v-3Zm0 7h10v3H8v-3Z" />
        </svg>
    );
}
