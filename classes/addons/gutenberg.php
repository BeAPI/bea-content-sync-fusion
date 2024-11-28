<?php

/**
 * Manage dynamic Gutenberg block's attributs and html.
 */
class BEA_CSF_Addon_Gutenberg {

	/**
	 * BEA_CSF_Addon_Gutenberg constructor.
	 */
	public function __construct() {
		if ( class_exists( 'WP_Block_Type' ) ) {
			add_filter( 'bea_csf_client_PostType_merge_data_to_transfer', [ $this, 'bea_csf_client_data_to_transfer' ], 10, 2 );
		}
	}

	/**
	 * Translate attributs and html
	 *
	 * @param array $data
	 * @param int $receiver_blog_id
	 *
	 * @return array
	 */
	public function bea_csf_client_data_to_transfer( $data, $receiver_blog_id ) {

		$receiver_blog_id = absint( $receiver_blog_id );
		$emitter_blog_id  = absint( $data['blogid'] );
		if ( empty( $data['post_content'] ) || empty( $receiver_blog_id ) || empty( $emitter_blog_id ) ) {
			return $data;
		}

		$blocks = parse_blocks( $data['post_content'] );
		if ( empty( $blocks ) ) {
			return $data;
		}
		$blocks = $this->translate_blocks( $blocks, $emitter_blog_id, $receiver_blog_id );
		$data['post_content'] = \Beapi\Gutenberg\BlocksSerializer::from_array( $blocks );

		return $data;
	}

	/**
	 * Loop over array of blocks and translate their attributs and html.
	 *
	 * @param array $blocks list of blocks parse from post content.
	 * @param int $emitter_blog_id ID of the emitter site.
	 * @param int $receiver_blog_id ID of the receiver site.
	 *
	 * @return array list of blocks with translated attributs and html.
	 */
	public function translate_blocks( array $blocks, int $emitter_blog_id, int $receiver_blog_id ): array {

		foreach ( $blocks as &$block ) {
			if ( empty( $block['blockName'] ) ) {
				continue;
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = $this->translate_blocks( $block['innerBlocks'], $emitter_blog_id, $receiver_blog_id );
			}

			if ( ! empty( $block['attrs'] ) ) {
				$block['attrs'] = $this->translate_block_attributes( $block['attrs'], $block['blockName'], $emitter_blog_id, $receiver_blog_id );
			}

			if ( ! empty( $block['innerContent'] ) ) {
				$block['innerContent'] = $this->translate_block_content( $block['innerContent'], $block['blockName'], $emitter_blog_id, $receiver_blog_id );
			}
		}
		unset( $block );

		return $blocks;
	}

	/**
	 * Translate attributs for a block.
	 *
	 * @param array $attributes current block's attributs.
	 * @param string $block_name current block's name.
	 * @param int $emitter_blog_id ID of the emitter site.
	 * @param int $receiver_blog_id ID of the receiver site.
	 *
	 * @return array translated attributs.
	 */
	private function translate_block_attributes( array $attributes, string $block_name, int $emitter_blog_id, int $receiver_blog_id ): array {

		if ( empty( $attributes ) ) {
			return $attributes;
		}

		// Attributs translations
		switch ( $block_name ) {
			case 'core/audio':
			case 'core/cover':
			case 'core/file':
			case 'core/image':
			case 'core/video':
				if ( ! empty( $attributes['id'] ) ) {
					$attributes['id'] = $this->translate_attribute(
						(int) $attributes['id'],
						'attachment',
						$emitter_blog_id,
						$receiver_blog_id
					);
				}
				break;
			case 'core/gallery':
				$image_ids = [];
				if ( ! empty( $attributes['ids'] ) ) {
					foreach ( $attributes['ids'] as $image_id ) {
						$local_id = BEA_CSF_Relations::get_object_for_any(
							'attachment',
							$emitter_blog_id,
							$receiver_blog_id,
							$image_id,
							$image_id
						);
					}
				}
				if ( ! empty( $local_id ) ) {
					$image_ids[] = $local_id;
				}
				$attributes['ids'] = $image_ids;
				break;
			case 'core/media-text':
				if ( ! empty( $attributes['mediaId'] ) ) {
					$attributes['mediaId'] = $this->translate_attribute(
						(int) $attributes['mediaId'],
						'attachment',
						$emitter_blog_id,
						$receiver_blog_id
					);
				}
				break;
			case 'core/block':
				if ( ! empty( $attributes['ref'] ) ) {
					$attributes['ref'] = $this->translate_attribute(
						(int) $attributes['ref'],
						'posttype',
						$emitter_blog_id,
						$receiver_blog_id
					);
				}
				break;
			default:
				break;
		}

		return apply_filters( 'bea_csf_gutenberg_translate_block_attributes', $attributes, $block_name, $emitter_blog_id, $receiver_blog_id );
	}

	/**
	 * Translate html for a block.
	 *
	 * @param array $html current block's html.
	 * @param string $block_name current block's name.
	 * @param int $emitter_blog_id ID of the emitter site.
	 * @param int $receiver_blog_id ID of the receiver site.
	 *
	 * @return array translated html.
	 */
	private function translate_block_content( array $html, string $block_name, int $emitter_blog_id, int $receiver_blog_id ): array {

		if ( empty( $html ) ) {
			return $html;
		}

		switch ( $block_name ) {
			case 'core/image':
			case 'core/media-text':
				foreach ( $html as &$item ) {

					// Update every occurrence of "wp-image-x" string with the correct image id.
					$item = preg_replace_callback(
						'@wp-image-(?<image_id>\d+)@',
						function ( array $matches ) use ( $emitter_blog_id, $receiver_blog_id ) {

							$local_id = BEA_CSF_Relations::get_object_for_any(
								'attachment',
								$emitter_blog_id,
								$receiver_blog_id,
								(int) $matches['image_id'],
								(int) $matches['image_id']
							);

							if ( ! empty( $local_id ) ) {
								return sprintf( 'wp-image-%d', $local_id );
							}

							return $matches[0];
						},
						$item
					);
				}
				unset( $item );
				break;
			case 'core/gallery':
				foreach ( $html as &$item ) {

					$item = preg_replace_callback(
						'@<img.*/>@U',
						function ( array $matches ) use ( $emitter_blog_id, $receiver_blog_id ) {
							return $this->translate_gallery_image( $matches[0], $emitter_blog_id, $receiver_blog_id );
						},
						$item
					);
				}
				unset( $item );
				break;
			default:
				break;
		}

		return $html;
	}

	/**
	 * Translate an ID from a block's attribut.
	 *
	 * @param int $value ID to translate.
	 * @param string $type Type of relation.
	 * @param int $emitter_blog_id ID of the emitter site.
	 * @param int $receiver_blog_id ID of the receiver site.
	 *
	 * @return int translated ID or original ID if no relations was found.
	 */
	private function translate_attribute( int $value, string $type, int $emitter_blog_id, int $receiver_blog_id ): int {

		$local_id = BEA_CSF_Relations::get_object_for_any(
			$type,
			$emitter_blog_id,
			$receiver_blog_id,
			$value,
			$value
		);

		return ! empty( $local_id ) ? $local_id : $value;
	}

	/**
	 * Gallery item translator helper.
	 *
	 * @param string $image_html gallery item html.
	 * @param int $emitter_blog_id ID of the emitter site.
	 * @param int $receiver_blog_id ID of the receiver site.
	 *
	 * @return string translated html.
	 */
	private function translate_gallery_image( string $image_html, int $emitter_blog_id, int $receiver_blog_id ): string {

		preg_match( '@data-id="(?<image_id>\d+)"@', $image_html, $matches );
		if ( empty( $matches['image_id'] ) ) {
			return $image_html;
		}

		$local_id = BEA_CSF_Relations::get_object_for_any(
			'attachment',
			$emitter_blog_id,
			$receiver_blog_id,
			(int) $matches['image_id'],
			(int) $matches['image_id']
		);

		if ( empty( $local_id ) ) {
			return $image_html;
		}

		// Update every occurrence of 'data-id="x"' string with the correct image id.
		$image_html = preg_replace_callback(
			'@data-id="(?<image_id>\d+)"@',
			function ( array $matches ) use ( $local_id ) {
				return sprintf( 'data-id="%d"', $local_id );
			},
			$image_html
		);

		// Update every occurrence of 'data-link="x"' string with the correct image link.
		$image_html = preg_replace_callback(
			'@data-link="(?<image_link>[^"]+)"@',
			function ( array $matches ) use ( $local_id ) {

				$attachment_url = get_permalink( $local_id );

				if ( false !== $attachment_url ) {
					return sprintf( 'data-link="%s"', esc_url_raw( $attachment_url ) );
				}

				return $matches[0];
			},
			$image_html
		);

		// Update every occurrence of 'wp-image-x' string with the correct image id.
		$image_html = preg_replace_callback(
			'@wp-image-(?<image_id>\d+)@',
			function ( array $matches ) use ( $local_id ) {
				return sprintf( 'wp-image-%d', $local_id );
			},
			$image_html
		);

		return $image_html;
	}
}
