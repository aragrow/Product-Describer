import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

registerBlockType('custom/projects-list', {
    title: __('Projects List', 'textdomain'),
    description: __('Displays the 10 most recent projects.', 'textdomain'),
    icon: 'list-view',
    category: 'widgets',
    edit() {
        const blockProps = useBlockProps({
            className: 'projects-block-editor',
        });

        return (
            <div {...blockProps}>
                <p>{__('Projects List will display here on the frontend.', 'textdomain')}</p>
            </div>
        );
    },
    save() {
        // The content is rendered dynamically, so save function returns null.
        return null;
    },
});
