<?php
/**
* Headings actions
*
* 
* @package      Customizr
* @subpackage   classes
* @since        3.1.0
* @author       Nicolas GUILLAUME <nicolas@themesandco.com>
* @copyright    Copyright (c) 2013, Nicolas GUILLAUME
* @link         http://themesandco.com/customizr
* @license      http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/
if ( ! class_exists( 'TC_headings' ) ) :
  class TC_headings {
      static $instance;
      function __construct () {
        self::$instance =& $this;
        //set actions and filters for single post and page headings
        add_action ( 'template_redirect'             , array( $this , 'tc_set_single_post_page_heading_hooks') );
        //set actions and filters for archives headings
        add_action ( 'template_redirect'             , array( $this , 'tc_set_archives_heading_hooks') );
        //Custom Bubble comment since 3.2.6
        add_filter ( 'tc_bubble_comment'             , array( $this , 'tc_custom_bubble_comment') );
      }


    

      /**
      * @return void
      * set up hooks for archives headings
      * callback of template_redirect
      *
      * @package Customizr
      * @since Customizr 3.2.6
      */
      function tc_set_archives_heading_hooks() {
        //is there anything to render in the current context ?
        if ( ! $this -> tc_archive_title_and_class_callback() )
          return;

        //Headings for archives, authors, search, 404
        add_action ( '__before_loop'                  , array( $this , 'tc_headings_view' ) );
        //Set archive icon with customizer options (since 3.2.0)
        add_filter ( 'tc_archive_icon'                , array( $this , 'tc_set_archive_icon' ) );
        
        add_filter( 'tc_archive_header_class'         , array( $this , 'tc_archive_title_and_class_callback'), 10, 2 );
        add_filter( 'tc_headings_content'             , array( $this , 'tc_archive_title_and_class_callback'), 10, 1 );
        global $wp_query;
        if ( tc__f('__is_home') || $wp_query -> is_posts_page )
          add_filter( 'tc_archive_headings_separator' , '__return_false' );
      }



      /**
      * @return void
      * set up hooks for single post and page headings
      * callback of template_redirect
      *
      * @package Customizr
      * @since Customizr 3.2.6
      */
      function tc_set_single_post_page_heading_hooks() {
        //don't display titles for some post formats
        if( in_array( get_post_format(), apply_filters( 'tc_post_formats_with_no_header', TC_init::$instance -> post_formats_with_no_header ) ) )
          return;

        //by default don't display the title of the front page
        if( apply_filters('tc_show_page_title', is_front_page() && 'page' == get_option( 'show_on_front' ) ) )
          return;

        //Set single post/page icon with customizer options (since 3.2.0)
        add_filter ( 'tc_content_title_icon'          , array( $this , 'tc_set_post_page_icon' ) );
        //Headings for post, page, attachment
        add_action ( '__before_content'               , array( $this , 'tc_headings_view' ) );
        add_filter ( 'tc_headings_content'            , array( $this , 'tc_post_page_title_callback'), 10, 2 );
        //Create the Customizr title
        add_filter( 'the_title'                       , array( $this , 'tc_content_heading_title' ) , 0 );
        //Add comment bubble
        add_filter( 'the_title'                       , array( $this , 'tc_add_comment_bubble_after_title' ), 1 );
        //Add edit link
        add_filter( 'the_title'                       , array( $this , 'tc_add_edit_link_after_title' ), 2 );
        //No hr if not singular
        if ( ! is_singular() )
          add_filter( 'tc_content_headings_separator' , '__return_false' );
      }



      /**
      * Generic heading view : archives, author, search, 404 and the post page heading (if not font page)
      * This is the place where every heading content blocks are hooked
      *
      * @package Customizr
      * @since Customizr 3.1.0
      */
      function tc_headings_view() {
        $_heading_type = ( '__before_content' == current_filter() ) ? 'content' : 'archive';
        ob_start();
        ?>
        <header class="<?php echo implode( ' ' , apply_filters( "tc_{$_heading_type}_header_class", array('entry-header'), $_return_class = true ) ); ?>">
          <?php 
            do_action( "__before_{$_heading_type}_title" );
            echo apply_filters( "tc_headings_content", '' , $_heading_type );
            do_action( "__after_{$_heading_type}_title" );

            echo apply_filters( "tc_{$_heading_type}_headings_separator", '<hr class="featurette-divider '.current_filter(). '">' );
          ?>
        </header>
        <?php
        $html = ob_get_contents();
        if ($html) ob_end_clean();
        echo apply_filters( 'tc_headings_view', $html );
      }//end of function




      /**
      * Callback for tc_headings_content
      * @return  string
      *
      * @package Customizr
      * @since Customizr 3.2.6
      */
      function tc_post_page_title_callback( $_content , $_heading_type ) {
        return sprintf('<%1$s class="entry-title %2$s">%3$s</%1$s>',
              apply_filters( 'tc_content_title_tag' , is_singular() ? 'h1' : 'h2' ),
              apply_filters( 'tc_content_title_icon', 'format-icon' ),
              get_the_title()
        );
      }



      /**
      * Callback for get_the_title
      * @return  string
      *
      * @package Customizr
      * @since Customizr 3.2.6
      */
      function tc_content_heading_title( $_title ) {
        //Must be in the loop
        if ( ! in_the_loop() )
          return $_title;

        //gets the post/page title
        if ( is_singular() || ! apply_filters('tc_display_link_for_post_titles' , true ) )
          return is_null($_title) ? apply_filters( 'tc_no_title_post', __( '{no title} Read the post &raquo;' , 'customizr' ) )  : $_title;
        else
          return sprintf('<a href="%1$s" title="%2$s" rel="bookmark">%3$s</a>',
            get_permalink(),
            sprintf( apply_filters( 'tc_post_link_title' , __( 'Permalink to %s' , 'customizr' ) ) , esc_attr( strip_tags( $_title ) ) ),
            is_null($_title) ? apply_filters( 'tc_no_title_post', __( '{no title} Read the post &raquo;' , 'customizr' ) )  : $_title
          );//end sprintf
      }




      /**
      * Callback for get_the_title
      * @return  string
      *
      * @package Customizr
      * @since Customizr 3.2.6
      */
      function tc_add_comment_bubble_after_title( $_title ) {
        //Must be in the loop
        if ( ! in_the_loop() )
          return $_title;

        //when are we showing the comments number in title?
        $comments_enabled                  = ( 1 == esc_attr( tc__f( '__get_option' , 'tc_page_comments' )) && comments_open() && get_comments_number() != 0 && !post_password_required() && is_page() ) ? true : false;
        $comments_enabled                  = ( comments_open() && get_comments_number() != 0 && !post_password_required() && !is_page() ) ? true : $comments_enabled;
        if ( ! apply_filters( 'tc_comments_in_title', $comments_enabled ) )
          return $_title;

        $_default_bubble_comment                    = apply_filters( 
          'tc_bubble_comment',
          sprintf('<span class="fs1 icon-bubble" %1$s></span><span class="inner">%2$s</span>',
            apply_filters( 'tc_comment_bubble_style' , ( 0 == get_comments_number() ) ? 'style="color:#ECECEC" ':'' ),
            get_comments_number()
          )
        );

        //checks if comments are opened AND if there are any comments to display
        return sprintf('%1$s <span class="comments-link"><a href="%2$s#tc-comment-title" title="%3$s %4$s">%5$s</a></span>',
          $_title,
          is_singular() ? '' : get_permalink(),
          __( 'Comment(s) on' , 'customizr' ),
          esc_attr( strip_tags( $_title ) ),
          $_default_bubble_comment
        );
      }




      /**
      * Callback for get_the_title
      * @return  string
      *
      * @package Customizr
      * @since Customizr 3.2.6
      */
      function tc_add_edit_link_after_title( $_title ) {
        //Must be in the loop
        if ( ! in_the_loop() )
          return $_title;

        //when are we displaying the edit link?
        $edit_enabled                      = ( (is_user_logged_in()) && current_user_can('edit_pages') && is_page() ) ? true : false;
        $edit_enabled                      = ( (is_user_logged_in()) && current_user_can('edit_post' , get_the_ID() ) && ! is_page() ) ? true : $edit_enabled;
        if ( ! apply_filters( 'tc_edit_in_title', $edit_enabled ) )
          return $_title;

        return sprintf('%1$s <span class="edit-link btn btn-inverse btn-mini"><a class="post-edit-link" href="%2$s" title="%3$s">%3$s</a></span>',
          $_title,
          get_edit_post_link(),
          __( 'Edit' , 'customizr' )
        );

      }



      /**
      * Filter tc_content_title_icon
      * @return  boolean
      *
      * @package Customizr
      * @since Customizr 3.2.0
      */
      function tc_set_post_page_icon( $_bool ) {
          if ( is_page() )
            $_bool = ( 0 == esc_attr( tc__f( '__get_option' , 'tc_show_page_title_icon' ) ) ) ? false : $_bool;
          if ( is_single() && ! is_page() )
            $_bool = ( 0 == esc_attr( tc__f( '__get_option' , 'tc_show_post_title_icon' ) ) ) ? false : $_bool;
          if ( ! is_single() )
            $_bool = ( 0 == esc_attr( tc__f( '__get_option' , 'tc_show_post_list_title_icon' ) ) ) ? false : $_bool;
          //last condition
          return ( 0 == esc_attr( tc__f( '__get_option' , 'tc_show_title_icon' ) ) ) ? false : $_bool;
      }




      /**
      * Filter tc_archive_icon
      * @return  boolean
      * 
      * @package Customizr
      * @since Customizr 3.2.0
      */
      function tc_set_archive_icon( $_bool ) {
          $_bool = ( 0 == esc_attr( tc__f( '__get_option' , 'tc_show_archive_title_icon' ) ) ) ? false : $_bool;
          //last condition
          return ( 0 == esc_attr( tc__f( '__get_option' , 'tc_show_title_icon' ) ) ) ? false : $_bool;
      }




      /**
      * Return 1) the archive title html content OR 2) the archive title class OR 3) the boolean
      * @return  boolean
      * 
      * @package Customizr
      * @since Customizr 3.2.0
      */
      function tc_archive_title_and_class_callback( $_title = null, $_return_class = false ) {
        //declares variables to return
        $content          = false;
        $_header_class     = array();

        //case page for posts but not on front
        global $wp_query;
        if ( $wp_query -> is_posts_page && ! is_front_page() ) {
          //get page for post ID
          $page_for_post_id = get_option('page_for_posts');
          $_header_class   = array('entry-header');
          $content        = sprintf('<%1$s class="entry-title %2$s">%3$s</%1$s>',
                apply_filters( 'tc_content_title_tag' , 'h1' ),
                apply_filters( 'tc_content_title_icon', 'format-icon' ),
                get_the_title( $page_for_post_id )
           );
          $content        = apply_filters( 'tc_page_for_post_header_content', $content );
        }


        //404
        if ( is_404() ) {
          $_header_class   = array('entry-header');
          $content        = sprintf('<h1 class="entry-title %1$s">%2$s</h1>',
                apply_filters( 'tc_archive_icon', '' ),
                apply_filters( 'tc_404_title' , __( 'Ooops, page not found' , 'customizr' ) )
           );
          $content        = apply_filters( 'tc_404_header_content', $content );
        }

        //search results
        if ( is_search() && !is_singular() ) {
          $_header_class   = array('search-header');
          $content        = sprintf( '<div class="row-fluid"><div class="%1$s"><h1 class="%2$s">%3$s%4$s %5$s </h1></div><div class="%6$s">%7$s</div></div>',
                apply_filters( 'tc_search_result_header_title_class', 'span8' ),
                apply_filters( 'tc_archive_icon', 'format-icon' ),
                have_posts() ? '' :  __( 'No' , 'customizr' ).'&nbsp;' ,
                apply_filters( 'tc_search_results_title' , __( 'Search Results for :' , 'customizr' ) ),
                '<span>' . get_search_query() . '</span>',
                apply_filters( 'tc_search_result_header_form_class', 'span4' ),
                have_posts() ? get_search_form(false) : ''
          );
          $content       = apply_filters( 'tc_search_results_header_content', $content );
        }
        
        //author's posts page
        if ( !is_singular() && is_author() ) {
          //gets the user ID
          $user_id = get_query_var( 'author' );
          $_header_class   = array('archive-header');
          $content    = sprintf( '<h1 class="%1$s">%2$s %3$s</h1>',
                  apply_filters( 'tc_archive_icon', 'format-icon' ),
                  apply_filters( 'tc_author_archive_title' , __( 'Author Archives :' , 'customizr' ) ),
                  '<span class="vcard"><a class="url fn n" href="' . esc_url( get_author_posts_url( $user_id ) ) . '" title="' . esc_attr( get_the_author_meta( 'display_name' , $user_id ) ) . '" rel="me">' . get_the_author_meta( 'display_name' , $user_id ) . '</a></span>' 
          );
          if ( apply_filters ( 'tc_show_author_meta' , get_the_author_meta( 'description', $user_id  ) ) ) {
            $content    .= sprintf('%1$s<div class="author-info"><div class="%2$s">%3$s</div></div>',

              apply_filters( 'tc_author_meta_separator', '<hr class="featurette-divider '.current_filter().'">' ),

              apply_filters( 'tc_author_meta_wrapper_class', 'row-fluid' ),

              sprintf('<div class="%1$s">%2$s</div><div class="%3$s"><h2>%4$s</h2><p>%5$s</p></div>',
                  apply_filters( 'tc_author_meta_avatar_class', 'comment-avatar author-avatar span2'),
                  get_avatar( get_the_author_meta( 'user_email', $user_id ), apply_filters( 'tc_author_bio_avatar_size' , 100 ) ),
                  apply_filters( 'tc_author_meta_content_class', 'author-description span10' ),
                  sprintf( __( 'About %s' , 'customizr' ), get_the_author() ),
                  get_the_author_meta( 'description' , $user_id  )
              )
            );
          }
          $content       = apply_filters( 'tc_author_header_content', $content );
        }

        //category archives
        if ( !is_singular() && is_category() ) {
          $_header_class   = array('archive-header');
          $content    = sprintf( '<h1 class="%1$s">%2$s %3$s</h1>',
                apply_filters( 'tc_archive_icon', 'format-icon' ),
                apply_filters( 'tc_category_archive_title' , __( 'Category Archives :' , 'customizr' ) ),
                '<span>' . single_cat_title( '' , false ) . '</span>'
          );
          if ( apply_filters ( 'tc_show_cat_description' , category_description() ) ) {
            $content    .= sprintf('<div class="archive-meta">%1$s</div>',
              category_description()
            );
          }
          $content       = apply_filters( 'tc_category_archive_header_content', $content );
        }

        //tag archives
        if ( !is_singular() && is_tag() ) {
          $_header_class   = array('archive-header');
          $content    = sprintf( '<h1 class="%1$s">%2$s %3$s</h1>',
                apply_filters( 'tc_archive_icon', 'format-icon' ),
                apply_filters( 'tag_archive_title' , __( 'Tag Archives :' , 'customizr' ) ),
                '<span>' . single_tag_title( '' , false ) . '</span>'
          );
          if ( apply_filters ( 'tc_show_tag_description' , tag_description() ) ) {
            $content    .= sprintf('<div class="archive-meta">%1$s</div>',
              tag_description()
            );
                              }
          $content       = apply_filters( 'tc_tag_archive_header_content', $content );
        }

        //time archives
        if ( ! is_singular() && ( is_day() || is_month() || is_year() ) ) {
          $archive_type   = is_day() ? sprintf( __( 'Daily Archives: %s' , 'customizr' ), '<span>' . get_the_date() . '</span>' ) : __( 'Archives' , 'customizr' );
          $archive_type   = is_month() ? sprintf( __( 'Monthly Archives: %s' , 'customizr' ), '<span>' . get_the_date( _x( 'F Y' , 'monthly archives date format' , 'customizr' ) ) . '</span>' ) : $archive_type;
          $archive_type   = is_year() ? sprintf( __( 'Yearly Archives: %s' , 'customizr' ), '<span>' . get_the_date( _x( 'Y' , 'yearly archives date format' , 'customizr' ) ) . '</span>' ) : $archive_type;
          $_header_class   = array('archive-header');
          $content        = sprintf('<h1 class="%1$s">%2$s</h1>',
            apply_filters( 'tc_archive_icon', 'format-icon' ),
            $archive_type
          );
          $content        = apply_filters( 'tc_time_archive_header_content', $content );
        }

        return $_return_class ? $_header_class : $content;

      }//end of fn



      function tc_custom_bubble_comment( $_default ) {
        if ( 'default' == esc_attr( tc__f( '__get_option' , 'tc_comment_bubble_shape' ) ) )
          return $_default;
        if ( 0 == get_comments_number() ) 
          return '';
 
          return sprintf('<span class="my-custom-bubble">%1$s %2$s</span>',
                    get_comments_number(),
                    sprintf( _n( 'comment' , 'comments' , get_comments_number(), 'customizr' ),
                      number_format_i18n( get_comments_number(), 'customizr' )
                    )
          );
      }

  }//end of class
endif;