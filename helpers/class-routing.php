<?php

/**
 * Class Dispatch_Routing
 *
 */
class Dispatch_Routing {

  /**
   * @var array
   */
  private static $_url_vars = array();

  /**
   * @var array
   */
  private static $_url_routes = array();

  /**
   * @var array
   */
  private static $_branches = array();

  /**
   * @var Dispatch_Request
   */
  private static $_matched_request;

  /**
   * @var Dispatch_Branch
   */
  private static $_matched_branch;

  /**
   * @var array
   */
  private static $_matched_segments;

  /**
   * Set hooks for non-admin URLs.
   */
  static function on_load() {
    if ( ! is_admin() ) {
      add_action( 'dispatch_register_routing', array( __CLASS__, '_dispatch_register_routing_11' ), 11 );
      add_action( 'wp_loaded', array( __CLASS__, '_wp_loaded' ) );
      add_filter( 'do_parse_request', array( __CLASS__, '_do_parse_request' ), 10, 3 );
      Dispatch::register_helper( __CLASS__ );
    }
  }

  /**
   * @param $continue
   * @param $wp
   * @param $extra_query_vars
   *
   * @return bool
   */
  static function _do_parse_request( $continue, $wp, $extra_query_vars ) {
    if ( $continue ) {

      $parser = new Dispatch_Parser( array( 'routes' => self::$_url_routes ) );
      $inbound_url = $parser->inbound_url;

      /**
       * Applicable branches are one where the segment count matches the segment count of the inbound URL.
       */
      if ( $parser->has_applicable_branches() ) {

        /**
         * Instantiate a Request object based on Dispatch::inbound_url()
         */
        $request = new Dispatch_Request();

        /**
         * Grab the $branches that match the URL path segment count
         * We only need to consider those.
         *
         * @var Dispatch_Branch $branch
         */
        foreach( $parser->applicable_branches() as $template => $branch ) {
          if ( $branch->match_request( $request ) ) {
            self::$_matched_request = $request;
            self::$_matched_branch = $branch;
            $continue = false;
            break;
          }
        }
      }
    }
    return $continue;
  }

  /**
   * @param string $parameter
   * @return bool
   */
  static function has_url_var( $parameter ) {
    return isset( self::$_url_vars[trim( $parameter, '{}' )] );
  }

  /**
   * @param string $parameter
   * @return object
   */
  static function get_url_var( $parameter ) {
    $parameter = trim( $parameter, '{}' );
    return isset( self::$_url_vars[$parameter] ) ? (object)self::$_url_vars[$parameter] : false;
  }

  /**
   *
   */
  static function _dispatch_register_routing_11() {
    /*
     * Fixup URL vars to pull Post Type from Class Name if available.
     */
    self::$_url_vars = self::_set_post_types( self::$_url_vars );
    /*
     * Fixup URL routes to pull Post Type from Class Name if available.
     */
    self::$_url_routes = self::_set_post_types( self::$_url_routes );
  }

  /**
   * Fixup $args to pull Post Type from Class Name, if available.
   *
   * @param $args
   *
   * @return array
   */
  static function _set_post_types( $args ) {
    foreach( $args as $arg_name => $arg ) {
      $arg = wp_parse_args( $arg, array(
        'post_types' => array(),
        'class_name' => false,
      ));
      if ( ! empty( $arg['class_name'] ) ) {
        if ( isset( $arg['post_type'] ) ) {
          $arg['post_types'] = $arg['post_type'];
          unset( $arg['post_type'] );
        }
        if ( empty( $arg['post_types'] ) ) {
          if ( defined( $constant = "{$arg['class_name']}::POST_TYPE" ) ) {
            $args[$route_name]['post_types'] = constant( $constant );
          }
        }
        if ( is_string( $arg['post_types'] ) ) {
          $args[$arg_name]['post_types'] = array(
            $arg['class_name'] => $arg['post_types']
          );
        }
      }
    }
    return $args;
  }

  /**
   *
   */
  static function _wp_loaded() {
    do_action( 'dispatch_register_routing' );
  }

  /**
   * @param string $template
   * @param array $args
   */
  static function register_url_route( $template, $args = array() ) {
    self::$_url_routes[$template] = $args;
  }

  /**
   * @param string $parameter
   * @param array $args
   */
  static function register_url_var( $parameter, $args = array() ) {
    self::$_url_vars[trim( $parameter, '{}' )] = $args;
  }

  /**
   * Set $wp->query_vars for a named post of a given post type.
   *
   * @param string $post_name
   * @param string $post_type
   */
  static function route_named_post( $post_name, $post_type ) {
    global $wp;

    $post_type_object = get_post_type_object( $post_type );
    $query_var = isset( $post_type_object->query_var ) ? $post_type_object->query_var : $post_type;
    /**
     * Set the query vars as WordPress expects in order to load the Stack
     */
    $wp->query_vars = array(
      'page' => '',
      'name' => $post_name,
      'post_type' => $post_type,
      $query_var => $post_name,
    );
  }

  /**
   * Returns the path segment given ordinal index where the first segment is 1 (not 0).
   *
   * Written to grab URL segment to load context for post type pages from the URL in
   * the case where multiple URLs w/different contexts are used to load same post.
   * The context we needed this for was to display Brand hero on Downloadables page.
   *
   * @param int $segment_no
   * @param bool $relative
   *
   * @return string
   */
  static function get_segment( $segment_no, $relative = true ) {
    $segment_offset = $segment_no - 1;
    $inbound_url = Dispatch::inbound_url();
    $path_segments = $relative ? $inbound_url->relative_segments() : $inbound_url->segments();
    return isset( $path_segments[$segment_offset] ) ? $path_segments[$segment_offset] : false;
  }

  /**
   * Returns the count of URL path segments.
   *
   * @param bool $relative
   * @return int
   */
  static function get_segment_count( $relative = true ) {
    $inbound_url = Dispatch::inbound_url();
    return count( $relative ? $inbound_url->relative_segments() : $inbound_url->segments() );
  }

  /**
   * @return object
   */
  static function matched_request() {
    return self::$_matched_request;
  }

  /**
   * Return an array of name/value pairs for the URL slugs.
   *
   * The slugs are the array values and the template variables are the array indexes.
   *
   * @example If URL is "/foo/bar/baz/" and template is "{first}/{second}/{third}" then:
   *
   *          array(
   *            'first' => 'foo',
   *            'second' => 'bar',
   *            'third' => 'baz',
   *          );
   *
   * @return array
   */
  static function matched_segments() {
    if ( ! isset( self::$_matched_segments ) ) {
      self::$_matched_segments = self::$_matched_request->matched_segments();
    }
    return self::$_matched_segments;
  }

}
Dispatch_Routing::on_load();
