export type TableAlignment = 'left' | 'right' | 'center';

export type TableColumn<R = Record<string, unknown>> = {
    key: string;
    field?: keyof R & string;
    label?: string;
    align?: TableAlignment;
    headerAlign?: TableAlignment;
    width?: string;
};
