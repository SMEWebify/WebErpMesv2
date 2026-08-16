<?php

namespace App\Services\Files;

/**
 * Translation payload handed to the front-end file components.
 *
 * The document manager and the standalone viewers (task statu screen) both need
 * the same strings, so the map lives here rather than being duplicated in every
 * Blade mount point.
 */
class FileTranslations
{
    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            'loading'            => __('general_content.loading_trans_key'),
            'no_data'            => __('general_content.no_data_trans_key'),
            'download'           => __('general_content.download_trans_key'),
            'delete'             => __('general_content.delete_trans_key'),
            'comment'            => __('general_content.comment_trans_key'),
            'hashtags'           => __('general_content.hashtags_trans_key'),
            'role'               => __('general_content.file_role_trans_key'),
            'role_auto'          => __('general_content.file_role_auto_trans_key'),
            'all_roles'          => __('general_content.file_all_roles_trans_key'),
            'primary'            => __('general_content.file_primary_trans_key'),
            'set_as_primary'     => __('general_content.file_set_as_primary_trans_key'),
            'attached_files'     => __('general_content.attached_file_trans_key'),
            'dropzone_hint'      => __('general_content.file_dropzone_hint_trans_key'),
            'dropzone_formats'   => __('general_content.file_dropzone_formats_trans_key'),
            'select_a_file'      => __('general_content.file_select_a_file_trans_key'),
            'no_inline_preview'  => __('general_content.file_no_inline_preview_trans_key'),
            'open_new_tab'       => __('general_content.file_open_new_tab_trans_key'),
            'confirm_delete'     => __('general_content.file_confirm_delete_trans_key'),
            'reset_view'         => __('general_content.file_reset_view_trans_key'),
            'triangles'          => __('general_content.file_triangles_trans_key'),
            'entities'           => __('general_content.file_entities_trans_key'),
            'layers'             => __('general_content.file_layers_trans_key'),
            'solids'             => __('general_content.file_solids_trans_key'),
            'ignored'            => __('general_content.file_ignored_trans_key'),
            'no_drawable_entity' => __('general_content.file_no_drawable_entity_trans_key'),
            'brep_read_failed'   => __('general_content.file_brep_read_failed_trans_key'),
            'view_iso'           => __('general_content.file_view_iso_trans_key'),
            'view_front'         => __('general_content.file_view_front_trans_key'),
            'view_back'          => __('general_content.file_view_back_trans_key'),
            'view_top'           => __('general_content.file_view_top_trans_key'),
            'view_bottom'        => __('general_content.file_view_bottom_trans_key'),
            'view_left'          => __('general_content.file_view_left_trans_key'),
            'view_right'         => __('general_content.file_view_right_trans_key'),
        ];
    }
}
