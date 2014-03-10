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
   * @var array Array of URL template segments
   */
  var $template_segments;

  /**
   * @var string The right most segment of the $template
   */
  var $last_template_segment;

  /**
   * @var int The number of segments for this branch's $template
   */
  var $count;

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
    if ( empty( $this->args['callback'] ) ) {
      /**
       * If not callback, try the default approachs.
       */
      if ( ! empty( $request->args['post_types'] ) ) {
        $matched = $this->match_post_type( $request );
      }
    } else if ( is_callable( $this->args['callback'] ) ) {
      /**
       * If callback, use it.
       */
      $matched = $this->match_callback( $request );
    } else {
      /*
       * ...Houston, we have a problem.
       */
      if ( is_string( $this->args['callback'] ) ) {
        $message = __( 'ERROR: Callback %s for template %s is not a callable.', 'dispatch' );
        trigger_error( sprintf( $message, $this->args['callback'], $this->template ) );
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
   * @param object $request
   *
   * @return bool
   */
  function match_callback( $request ) {
    return call_user_func( $this->args['callback'], $request, $this );
  }

  /**
   * @param object $branch
   *
   * @return bool|WP_Query
   */
  function match_post_type( $branch ) {
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
