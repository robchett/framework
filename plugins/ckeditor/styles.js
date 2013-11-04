﻿CKEDITOR.stylesSet.add('default', [
    { name: 'Italic Title', element: 'h2', styles: { 'font-style': 'italic' } },
    { name: 'Subtitle', element: 'h3', styles: { 'color': '#aaa', 'font-style': 'italic' } },
    { name: 'Special Container',
        element: 'p',
        attributes: { 'class': 'boxed cf' }
    },
    { name: 'Title',
        element: 'h3',
        attributes: { 'class': 'title cf' }
    },
    /*
     { name: 'Strong',			element: 'strong', overrides: 'b' },
     { name: 'Emphasis',			element: 'em'	, overrides: 'i' },
     { name: 'Underline',		element: 'u' },
     { name: 'Strikethrough',	element: 'strike' },
     { name: 'Subscript',		element: 'sub' },
     { name: 'Superscript',		element: 'sup' },
     */

    { name: 'Marker', element: 'span', attributes: { 'class': 'marker' } },
    { name: 'Big', element: 'big' },
    { name: 'Small', element: 'small' },
    { name: 'Typewriter', element: 'tt' },

    { name: 'Computer Code', element: 'code' },
    { name: 'Keyboard Phrase', element: 'kbd' },
    { name: 'Sample Text', element: 'samp' },
    { name: 'Variable', element: 'var' },

    { name: 'Deleted Text', element: 'del' },
    { name: 'Inserted Text', element: 'ins' },

    { name: 'Cited Work', element: 'cite' },
    { name: 'Inline Quotation', element: 'q' },

    { name: 'Language: RTL', element: 'span', attributes: { 'dir': 'rtl' } },
    { name: 'Language: LTR', element: 'span', attributes: { 'dir': 'ltr' } },

    /* Object Styles */
    {
        name: 'Padded image',
        element: 'span',
        attributes: { 'class': 'left' }
    },
    {
        name: 'Styled image (left)',
        element: 'img',
        attributes: { 'class': 'left' }
    },

    {
        name: 'Styled image (right)',
        element: 'img',
        attributes: { 'class': 'right' }
    },

    {
        name: 'Compact table',
        element: 'table',
        attributes: {
            cellpadding: '5',
            cellspacing: '0',
            border: '1',
            bordercolor: '#ccc'
        },
        styles: {
            'border-collapse': 'collapse'
        }
    },

    { name: 'Borderless Table', element: 'table', styles: { 'border-style': 'hidden', 'background-color': '#E6E6FA' } },
    { name: 'Square Bulleted List', element: 'ul', styles: { 'list-style-type': 'square' } }
]);

