<?php
/**
 * The template for displaying blog posts
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();

?>
<div id="primary" class="content-area">
	<?php
	if ( function_exists( 'yoast_breadcrumb' ) ) {
		yoast_breadcrumb( '<p id="breadcrumbs yo">', '</p>' );
	}
	?>

	<main id="main" class="site-main">
		<?php
		while ( have_posts() ) :
			the_post();

			$auth_profile_link = get_post_meta( get_the_ID(), 'author-profiles', true );

			the_title( '<div class="blog_title"><p>', '</p></div>' );

			if ( ! empty( $auth_profile_link ) ) {
				echo '<div class="blog_author2"><p>' . wp_kses_post( $auth_profile_link ) . '</p></div>';
			}

			echo '<div class="blog_date"><p>' . get_the_date( 'F, j Y' ) . '</p></div>';

			if ( has_post_format( array( 'gallery', 'video', 'image' ) ) ) {
				get_template_part( 'template-parts/content', get_post_format() );
			} else {
				get_template_part( 'template-parts/content', 'single' );
			}

			if ( class_exists( 'Jetpack_Likes' ) ) {
				$custom_likes = new Jetpack_Likes();
				echo $custom_likes ? $custom_likes->post_likes( '' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			if ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'sharedaddy' ) && function_exists( 'sharing_display' ) ) :
				?>
				<h2 class="share-this heading-strike"><?php esc_html_e( 'Share This', 'siteorigin-unwind' ); ?></h2>
				<?php
				echo sharing_display(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			endif;

			if ( siteorigin_setting( 'navigation_post' ) ) :
				siteorigin_unwind_the_post_navigation();
			endif;

			if ( siteorigin_setting( 'blog_display_author_box' ) ) :
				siteorigin_unwind_author_box();
			endif;

			if ( ! is_attachment() && siteorigin_setting( 'blog_display_related_posts' ) ) :
				siteorigin_unwind_related_posts( $post->ID );
			endif;

			// If comments are open or we have at least one comment, load up the comment template.
			if ( comments_open() || get_comments_number() ) :
				comments_template();
			endif;

		endwhile; // End of the loop.
		?>
	</main><!-- #main -->
</div><!-- #primary -->

<?php
get_sidebar();

get_footer();
