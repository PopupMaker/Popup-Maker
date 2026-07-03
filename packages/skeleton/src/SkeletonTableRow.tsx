import { SkeletonBar } from './SkeletonBar';

export interface SkeletonTableRowCell {
	key: string;
	width?: string | number;
	height?: number;
	content?: React.ReactNode;
}

export interface SkeletonTableRowProps {
	cells: SkeletonTableRowCell[];
}

export const SkeletonTableRow: React.FC< SkeletonTableRowProps > = ( {
	cells,
} ) => (
	<tr>
		{ cells.map( ( cell ) => (
			<td key={ cell.key }>
				{ cell.content ?? (
					<SkeletonBar
						width={ cell.width ?? '55%' }
						height={ cell.height ?? 14 }
					/>
				) }
			</td>
		) ) }
	</tr>
);
