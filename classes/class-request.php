<?php

/**
 * Class Dispatch_Request
 */
class Dispatch_Request extends Dispatch_Base {

  /**
   * @var array
   */
  var $reversed_segments;

  /**
   * @var string
   */
  var $last_segment;

  /**
   * @var string
   */
  var $path;

  /**
   * @var string
   */
  var $query;

  /**
   * @var array
   */
  var $query_vars;

  /**
   * @var Dispatch_Branch
   */
  var $matched_branch;

  /**
   * @var bool|Dispatch_Request
   */
  var $child = false;

  /**
   * @var bool|Dispatch_Request
   */
  private $_parent = false;

  /**
   * @param bool|Dispatch_Url $inbound_url
   */
  function __construct( $inbound_url = false ) {
    if ( ! $inbound_url ) {
      $inbound_url = Dispatch::inbound_url();
    }
    $this->reversed_segments = $inbound_url->reversed_relative_segments();
    $this->last_segment = count( $this->reversed_segments ) ? $this->reversed_segments[0] : false;
    $this->path = $inbound_url->path;
    parent::__construct( $args );
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
  function matched_segments() {
    $branch = $this->matched_branch;
    $segments = array();
    $template_parts = explode( '/', $branch->template );
    foreach( $branch->template_segments as $index => $slug ) {
      $name = preg_replace( '#^\{(.*)\}$#', '$1', $template_parts[$index] );
      $segments[$name] = $slug;
    }
    return $segments;
  }


  /**
   * Returns a URL $request object that represents the the parent URL.
   *
   * For example, if we have a $request object for 'foo/bar/baz' this
   * function returns a $request object for 'foo/bar'
   *
   * @return Dispatch_Request
   */
  function parent() {

    if ( ! $this->_parent ) {
      /**
       * Make a copy so our changes don't affect the original $request object.
       */
      $parent = clone $this;

      /**
       * Give a reference back to its child.
       */
      $parent->child = $request;

      /**
       * Slice off 1st segment in array, which is last segment in URL.
       */
      array_shift( $parent->reversed_segments );

      /**
       * Set the new segment of "focus"
       */
      $parent->last_segment = $parent->reversed_segments[0];

      /**
       * Slice off the last segment from the URL path.
       */
      $parent->path = dirname( rtrim( $parent->path, '/' ) );

      /**
       * Now save it locally.
       */
      $this->_parent = $parent;
    }
    /**
     * And return it, we are done.
     */
    return $this->_parent;
  }

}
