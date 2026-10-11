<?php
defined( 'ABSPATH' ) || exit;

/** Count contract for newly prepared imports; the browser remains responsible for source observations. */
final class KREV_Import_Photo_Manifest {
    public static function validate( $manifest, $supplied, $required = false ) {
        if ( null === $manifest && ! $required ) return null; // Existing prepared links remain claimable.
        if ( ! is_array( $manifest ) || array_diff( array_keys( $manifest ), array( 'source_count', 'gallery_complete', 'partial_accepted', 'small_accepted' ) ) || ! isset( $manifest['source_count'], $manifest['gallery_complete'] ) || ! is_int( $manifest['source_count'] ) || $manifest['source_count'] < 0 || $manifest['source_count'] > 100 || ! is_bool( $manifest['gallery_complete'] ) || ( isset( $manifest['partial_accepted'] ) && ! is_bool( $manifest['partial_accepted'] ) ) || ( isset( $manifest['small_accepted'] ) && ! is_bool( $manifest['small_accepted'] ) ) ) return new WP_Error( 'import_photo_manifest', 'Inspect the full Facebook photo gallery and provide photo_manifest with source_count and gallery_complete. Update kniferevive-listing to 1.0.5; a single metadata thumbnail is not a verified gallery.', array( 'status' => 422 ) );
        $expected = min( $manifest['source_count'], 10 ); $partial = $manifest['partial_accepted'] ?? false;
        if ( $supplied > $expected || ( ! $partial && ( ! $manifest['gallery_complete'] || $supplied !== $expected || ! $expected ) ) ) return new WP_Error( 'import_photo_count', 'Photo count does not match the inspected Facebook gallery. Collect every photo (up to 10 for this import), or obtain the seller’s explicit acceptance of identified missing photos.', array( 'status' => 422 ) );
        return array( 'source_count' => $manifest['source_count'], 'gallery_complete' => $manifest['gallery_complete'], 'partial_accepted' => $partial, 'small_accepted' => $manifest['small_accepted'] ?? false );
    }

    public static function counts( $data, $copied = null ) {
        $listing = $data['listing'] ?? array();
        return array( 'found' => $data['photo_manifest']['source_count'] ?? null, 'submitted' => count( $data['image_urls'] ?? array() ) + count( $listing['gallery_ids'] ?? array() ) + ( empty( $listing['featured_image_id'] ) ? 0 : 1 ), 'copied' => $copied, 'gallery_verified' => $data['photo_manifest']['gallery_complete'] ?? false );
    }
}
