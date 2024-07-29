<?php
/*
 * Plugin Name:       Movies & Details
 * Description:       This is a basic Plugin
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Razel Ahmed
 * Author URI:        https://razelahmed.com
 */

 if ( ! defined('ABSPATH') ) {
  exit;
 }

class Movies_And_Details {
  public function __construct() {
    add_action('init', array( $this,'init') );
    add_action('init', array( $this,'register_taxonomy') );
    add_filter('the_content', array( $this,'add_movie_details') );
    add_filter('the_title', array( $this,'in_title_add_movie_year'), 10, 2 );
    add_filter('the_content', array( $this,'show_related_movies') );
  }

  public function init() {
    register_post_type('movie', array(
      'label' => 'movie',
      'labels' => array(
        'name'=> 'Movies',
        'singular_name'=> 'Movie',
        'add_new' => 'Add Ned Movie',
      ),
      'public' => true,
      'has_archive' => true,
      'supports' => array( 'title', 'editor', 'thumbnail' ),
      'taxonomies' => array( 'genre', 'actor', 'director', 'years', ),
    ));
  }

  public function register_taxonomy() {
    // Genre
    register_taxonomy('genre', array('movie'), array(
        'labels' => array(
            'name' => 'Genres',
            'singular_name' => 'Genre',
        ),
        'hierarchical' => true,
        'show_admin_column' => true,
    ));

    // Actor
    register_taxonomy('actor', array('movie'), array(
      'labels' => array(
          'name' => 'Actors',
          'singular_name' => 'Actor',
      ),
      'hierarchical' => true,
      'show_admin_column' => true,
    ));

    // Director
    register_taxonomy('director', array('movie'), array(
      'labels' => array(
          'name' => 'Directors',
          'singular_name' => 'Director',
      ),
      'hierarchical' => true,
      'show_admin_column' => true,
    ));

    // Year
    register_taxonomy('years', array('movie'), array(
      'labels' => array(
          'name' => 'Years',
          'singular_name' => 'Year',
      ),
      'rewrite' => array(
        'slug' => 'movie-year',
      ),
      'hierarchical' => false,
      'show_admin_column' => true,
    ));

  }

  /**
   * Add Movie Details
   */

   public function add_movie_details($content) {
    // dump ( get_the_term_list( get_the_ID(), 'genre', '<h3>', ',', '</h3>') );
    $post = get_post( get_the_ID() );
    // dump($post);
    if ( $post->post_type !== 'movie' ) {
      return $content;
    }

    $genre = get_the_term_list( get_the_ID(), 'genre', '<p>', ', ', '</p>');
    $actor = get_the_term_list( get_the_ID(), 'actor', '<p>', ', ', '</p>');
    $director = get_the_term_list( get_the_ID(), 'director', '<p>', ', ', '</p>');
    $year = get_the_term_list( get_the_ID(), 'years', '<p>', ', ', '</p>');

    if ( $genre ) {
      $content .=  ' <p>Genre</p> ' . $genre;
    }

    if ( $actor ) {
      $content .=  ' <p>Actor</p> ' . $actor;
    }

    if ( $director ) {
      $content .=  ' <p>Director</p> ' . $director;
    }

    if ( ! is_wp_error( $year ) && $year ) {
      $content .=  ' <p>Year</p> ' . $year;
    }


    return $content;

   }

  //  add year in the left side of a title
   public function in_title_add_movie_year( $post_title, $post_id ) {

      $post = get_post( get_the_ID() );

      if ( $post->post_type !== 'movie' ) {
        return $post_title;
      }

      $years = get_the_terms( $post, 'years' );
      // dump( $years );

      if ( $years ) {
        $title_year = ' ( ' . $years[0]->name . ' ) ';
      }

      return $post_title . $title_year;

   }


   /***
    * Show Related Movies
    */

    public function show_related_movies( $content ) { 
      $genre = get_the_terms( get_the_ID(), 'genre');

      if ( ! $genre ) { 
        return $content;
      }

      // dump($genre);
      // dump( wp_list_pluck( $genre,'term_id') );

      // custom query with taxonomy
      $query = new WP_Query( array(
        'post_type' => 'movie',
        'post__not_in' => array(
          get_the_ID(),
        ),
        'tax_query' => array( 
          'relation' => 'OR',
          array(
            'taxonomy' => 'genre',
            'terms' => wp_list_pluck( $genre,'term_id'),
          ),
         ),
      ) );

      // dump( $query->get_posts() );

      if ( ! $query->have_posts() ) {
        return $content;
      }

      $related_movies = '<h3>Related Movies</h3>';

      $related_movies .= '<ul>';

      foreach ( $query->get_posts() as $mov ) {
        $related_movies .= sprintf('<li><a href="%s">%s</a></li>', get_permalink( $mov ), get_the_title( $mov ) );
        // wihtout year
        // $related_movies .= sprintf('<li><a href="%s">%s</a></li>', get_permalink( $mov ),  $mov->post_title  );
      }

      $related_movies .= '</ul>';

      return $content . $related_movies;
    }

}

new Movies_And_Details();


// Helper function for print_r
function dump ( $var ) {
  echo '<pre>';
  print_r($var);
  echo '</pre>';
}

