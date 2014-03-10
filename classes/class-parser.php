<?php

/**
 * Class Dispatch_Parser
 */
class Dispatch_Parser {

  /**
   * @var
   */
  var $inbound_url;

  /**
   * Contains an index of branches by URL path segment count.
   *
   * @example "foo/bar" is 2 segments.
   * @example "foo/bar/baz" is 3 segments.
   *
   * @var array
   */
  private $_segment_counts;

  /**
   * @var
   */
  private $_vars = array();

  /**
   *
   */
  function __construct( $args = array() ) {
    $this->inbound_url = isset( $args['inbound_url'] ) ? $args['inbound_url'] : Dispatch::inbound_url();
    if ( isset( $args['routes'] ) ) {
      $this->parse_routes( $args['routes'] );
    }
  }

  /**
   * @param array $routes
   * @return array
   */
  function parse_routes( $routes ) {
    $branches = array();
    foreach( $routes as $template => $args ) {
      $args = wp_parse_args( $args );
      $segments = explode( '/', trim( $template, '/' ) );
      $branch = $this->parse_segments( $template, $segments, $args );
      if ( ! isset( $branches[$branch->last_template_segment] ) ) {
        $branches[$branch->last_template_segment] = $branch;
      } else {
        $branches[$branch->last_template_segment]->children = $branch->children;
      }
    }
    return $branches;
  }

  /**
   * @param string $template
   * @param array $segments
   * @param array $args
   * @param int $count
   *
   * @return Dispatch_Branch
   */
  function parse_segments( $template, $segments, $args = array(), $count = 1 ) {
    $segment = $segments[0];
    $branch = new Dispatch_Branch( array(
      'template' => $template,
      'segment' => $segment,
      'count' => $count,
      'parent' => false,
    ));
    if ( 1 == count( $segments ) ) {
      $branches = array();
      $segments = explode( '/', $template );
      $segment_args = $args;
    } else {
      $child_branch = $this->parse_segments( $template, array_slice( $segments, 1 ), $args, $count + 1 );
      $child_branch->parent = $branch;
      $branches = array( $child_branch->last_template_segment => $child_branch );
      $segments = $child_branch->template_segments;
      array_pop( $segments );
      $branch->template = implode( '/', $segments );
      $segment_args = array();
    }
    $template = implode( '/', $segments );
    if ( $this->has_template_branches( $count, $template ) ) {
      $branches = $this->_merge_template_branches( $count, $template, $branches );
    }
    $branch->children = $branches;
    $branch->template_segments = $segments;
    if ( Dispatch::has_url_var( $segment ) ) {
      /**
       * Default to the segment args having higher priority than the params args.
       */
      $segment_args = array_merge( (array)Dispatch::get_url_var( $segment ), $segment_args );
    }
    $branch->args = $segment_args;
    $this->add_template_branches( $count, $template, $branch );
    return $branch;
  }

  /**
   * Do we have any branches for an applicable number of
   * @return bool
   */
  function has_applicable_branches() {
    $count = count( $this->inbound_url->relative_segments() );
    return isset( $this->_segment_counts[$count] ) ? 0 < count( $this->_segment_counts[$count] ) : false;
  }

  /**
   * Do we have any branches for an applicable number of
   * @return array
   */
  function applicable_branches() {
    $count = count( $this->inbound_url->reversed_relative_segments() );
    return isset( $this->_segment_counts[$count] ) ? $this->_segment_counts[$count] : array();
  }

  /**
   * @param $count
   * @param $template
   *
   * @return bool
   */
  function has_template_branches( $count, $template ) {
    return isset( $this->_segment_counts[$count][$template] );
  }

  /**
   * @param $count
   * @param $template
   * @param $branch
   */
  function add_template_branches( $count, $template, $branch ) {
    $this->_segment_counts[$count][$template] = $branch;
  }
  /**
   * @param $count
   * @param $template
   * @param $branches
   *
   * @return array
   */
  private function _merge_template_branches( $count, $template, $branches ) {
    return array_merge( $this->_segment_counts[$count][$template]->children, $branches );
  }

}
