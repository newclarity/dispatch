<?php

/**
 * Class Dispatch_Branch
 */
class Dispatch_Branch extends Dispatch_Base {

  /**
   * @var string The URL template this Branch matches
   */
  var $template;

  /**
   * @var Dispatch_Branch The parent Branch for this Branch
   */
  var $parent;

  /**
   * @var array The child Branches of this Branch
   */
  var $children;

  /**
   * @var string The default arguments registered for this Branch.
   */
  var $args;

  /**
   * @var Dispatch_Request
   */
  var $matched_request;

  /**
   * @var array Array of URL template segments
   */
  private $_template_segments = false;

  /**
   * @var string The right most segment of the $template
   */
  private $_last_template_segment = false;


  function __construct( $template, $args ) {
    $this->template = $template;
    $this->last_template_segment =
    parent::__construct( $args );
  }

  function template_segments() {
    if ( ! $this->_template_segments ) {
      $this->_template_segments = explode( '/', rtrim( $template, '/' ) );
    }
    return $this->_template_segments;
  }

  function set_template_segments( $segment ) {
    $this->_template_segments = $segment;
  }

  function last_template_segment() {
    if ( ! $this->_last_template_segment ) {
      $this->_last_template_segment = end( $this->template_segments() );
    }
    return $this->_last_template_segment;
  }


  /**
   * @param object $request
   *
   * @return bool
   *
   * @todo This is where we can make a lot of performance improvements
   *       and simplify routing for the most common use-cases.
   *
   */
  function match_request( $request ) {
    $matched = false;
    if ( empty( $this->args['matcher'] ) ) {
      /**
       * If no matcher, try the default approachs.
       */
      if ( ! empty( $request->args['post_types'] ) ) {
        $matched = $this->try_post_type( $request );
      }
    } else if ( is_callable( $this->args['matcher'] ) ) {
      /**
       * If matcher, use it.
       */
      $matched = $this->try_matcher( $request );
    } else {
      /*
       * ...Houston, we have a problem.
       */
      if ( is_string( $this->args['matcher'] ) ) {
        $message = __( 'ERROR: Callback %s for template %s is not a callable.', 'dispatch' );
        trigger_error( sprintf( $message, $this->args['matcher'], $this->template ) );
      } else {
        $message = __( 'ERROR: Callback for template %s is not a callable.', 'dispatch' );
        trigger_error( sprintf( $message, $this->template ) );
      }
      if ( $matched ) {
        $request->matched_branch = $this;
        $this->matched_request = $return;
      }
    }
    return $matched;
  }

  /**
   * @param Dispatch_Request $request
   *
   * @return bool
   */
  function try_matcher( $request ) {
    return call_user_func( $this->args['matcher'], $request, $this );
  }

  /**
   * @param Dispatch_Branch $branch
   *
   * @return bool|WP_Query
   */
  function try_post_type( $branch ) {
    $query = new WP_Query( array(
      'post_status' => 'publish',
      'post_type' => $branch->args['post_types'],
      'posts_per_page' => 1,
      'name' => $request->last_segment,
    ));
    ;
    if ( 0 == count( $query->posts ) ) {
      $matched = false;
    } else {
      Dispatch::route_named_post( $request->last_segment, $query->post->post_type );
      $matched = $query;
    }
    return $matched;
  }

}
