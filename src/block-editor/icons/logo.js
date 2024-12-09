import { createElement } from '@wordpress/element';

const LogoIcon = createElement(
	'svg',
	{
		viewBox: '0 0 106 84',
		width: 24,
		height: 24,
		className: 'popup-trigger-button-svg',
	},
	createElement( 'path', {
		d: 'M 74.98 0.00 L 80.18 0.00 C 86.85 0.96 93.11 3.19 97.92 8.09 C 102.82 12.91 105.07 19.19 106.00 25.89 L 106.00 29.25 C 105.01 36.93 101.84 43.76 95.96 48.90 C 85.62 57.23 75.10 65.38 64.88 73.86 C 58.14 79.85 49.63 82.94 40.76 84.00 L 36.17 84.00 C 27.56 83.00 19.39 80.03 12.89 74.16 C 5.17 67.38 1.08 57.89 0.00 47.78 L 0.00 43.19 C 1.06 33.34 4.97 24.08 12.35 17.32 C 19.55 10.62 29.39 7.33 38.98 6.07 C 50.98 4.07 63.06 2.41 74.98 0.00 Z',
		fill: '#98b729',
	} ),
	createElement( 'path', {
		d: 'M 73.27 3.38 C 78.51 2.46 83.84 3.16 88.72 5.25 C 99.12 9.98 105.12 21.94 102.29 33.09 C 100.93 39.34 97.06 44.25 92.19 48.20 C 84.32 54.30 76.63 60.62 68.82 66.78 C 65.27 69.54 61.99 72.75 58.21 75.17 C 53.04 78.31 47.09 80.42 41.04 80.90 C 26.64 81.98 12.34 73.74 6.37 60.53 C 0.78 48.69 2.33 34.56 10.17 24.12 C 16.07 16.10 25.11 11.68 34.69 9.75 C 47.55 7.61 60.45 5.72 73.27 3.38 Z',
		fill: '#262d2b',
	} ),
	createElement( 'path', {
		d: 'M 73.39 7.40 C 79.51 6.31 85.83 7.34 90.84 11.17 C 97.78 16.34 100.76 25.75 97.94 33.97 C 96.07 39.49 92.17 43.26 87.63 46.67 C 80.70 52.04 73.92 57.62 67.04 63.05 C 61.52 67.32 57.24 72.00 50.55 74.56 C 39.66 79.19 26.67 77.04 17.82 69.21 C 10.09 62.55 6.01 52.13 7.21 41.99 C 8.21 32.78 13.46 24.27 21.21 19.22 C 29.30 14.01 37.69 13.29 46.90 11.83 C 55.73 10.34 64.58 9.05 73.39 7.40 Z',
		fill: '#98b729',
	} ),
	createElement( 'path', {
		d: 'M 79.33 11.15 C 80.91 11.34 82.49 11.77 84.05 12.13 C 83.96 13.78 83.90 15.42 83.83 17.07 C 85.21 18.44 86.59 19.81 87.96 21.19 C 89.56 21.12 91.16 21.05 92.76 20.97 C 93.19 22.58 93.62 24.19 94.07 25.79 C 92.62 26.56 91.18 27.34 89.74 28.11 C 89.27 30.00 88.80 31.89 88.29 33.77 C 89.17 35.11 90.05 36.46 90.93 37.80 C 89.75 38.99 88.56 40.18 87.37 41.36 C 86.03 40.50 84.69 39.65 83.36 38.79 C 81.43 39.31 79.50 39.83 77.57 40.33 C 76.86 41.76 76.14 43.18 75.44 44.61 C 73.84 44.14 72.22 43.70 70.60 43.30 C 70.70 41.70 70.79 40.09 70.89 38.49 C 69.46 37.08 68.05 35.65 66.64 34.22 C 65.07 34.33 63.50 34.41 61.94 34.52 C 61.54 32.88 61.09 31.25 60.61 29.63 C 62.04 28.92 63.45 28.20 64.87 27.48 C 65.38 25.56 65.93 23.65 66.45 21.74 C 65.57 20.37 64.69 19.01 63.80 17.65 C 64.99 16.46 66.17 15.27 67.36 14.08 C 68.70 14.97 70.04 15.86 71.38 16.75 C 73.20 16.26 75.02 15.78 76.84 15.32 C 77.62 13.91 78.39 12.46 79.33 11.15 Z',
		fill: '#262d2b',
	} ),
	createElement( 'path', {
		d: 'M 31.46 18.53 C 35.73 17.41 39.75 17.90 44.06 18.38 C 43.69 20.25 43.38 22.13 43.00 23.99 C 46.30 25.32 49.40 26.46 52.10 28.89 C 56.07 32.21 58.00 36.65 59.46 41.49 C 61.32 41.26 63.19 41.04 65.06 40.81 C 65.30 45.35 65.55 49.64 64.02 54.02 C 62.82 57.89 60.52 60.95 58.09 64.10 C 56.66 62.88 55.24 61.65 53.81 60.43 C 50.80 62.88 47.90 65.17 44.07 66.21 C 39.50 67.65 35.11 67.00 30.55 65.99 C 29.84 67.72 29.12 69.46 28.40 71.19 C 24.48 69.34 20.78 67.44 17.87 64.12 C 14.90 61.08 13.34 57.40 11.80 53.51 C 13.55 52.89 15.31 52.27 17.06 51.65 C 16.43 47.16 15.95 42.88 17.48 38.49 C 18.70 34.52 21.22 31.56 23.95 28.54 C 22.80 27.05 21.69 25.54 20.55 24.05 C 23.99 21.67 27.30 19.46 31.46 18.53 Z',
		fill: '#262d2b',
	} ),
	createElement( 'path', {
		d: 'M 76.34 24.32 C 79.21 23.52 81.89 26.79 80.48 29.46 C 79.35 31.71 76.40 32.21 74.62 30.38 C 72.72 28.34 73.67 25.06 76.34 24.32 Z',
		fill: '#98b729',
	} ),
	createElement( 'path', {
		d: 'M 33.46 26.53 C 40.08 24.87 47.25 27.17 51.85 32.16 C 57.28 37.94 58.59 46.87 54.94 53.94 C 51.18 61.61 42.36 65.97 33.97 64.14 C 25.47 62.43 18.97 54.70 18.77 46.02 C 18.32 36.96 24.64 28.60 33.46 26.53 Z',
		fill: '#98b729',
	} )
);

export default LogoIcon;
