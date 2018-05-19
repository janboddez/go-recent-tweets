<?php
/**
 * Plugin Name: GO Recent Tweets
 * Plugin URI: https://janboddez.be/
 * Description: Retrieve, cache and display your most recent tweets.
 * Version: 0.1.0
 * Author: Jan Boddez
 * Author URI: https://janboddez.be/
 * Text Domain: go-recent-tweets
 * License: GPL v2
 */

/**
 * Widget class
 */
class GO_Recent_Tweets_Widget extends WP_Widget {
	/**
	 * Sets up the widget.
	 */
	public function __construct() {
		parent::__construct(
			'go_recent_tweets_widget', 
			__( 'Recent Tweets', 'go-recent-tweets' ), 
			array( 'description' => __( 'Retrieve, cache (!) and display your most recent tweets.', 'go-recent-tweets' ) )
		);

		/*
		 * Loads up some basic styles that may or may not suffice for your
		 * website.
		 */
		// if ( is_active_widget( false, false, $this->id_base, true ) ) {
		// 	wp_enqueue_style( 'go-recent-tweets', plugins_url( 'go-recent-tweets/go-recent-tweets.css' ) );
		// }
	}

	/**
	 * Renders the actual widget.
	 */
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $args['before_widget'];

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		/*
		 * Retrieves the tweet list stored previously. (In this case, returns
		 * either an array or false.)
		 */
		$tweets = get_transient( 'go_recent_tweets' );

		if ( false === $tweets ) {
			$tweets = GO_Recent_Tweets::load_tweets();
		}

		if ( ! empty( $tweets ) && is_array( $tweets ) ) {
			/* Loops through the list and outputs the actual tweets. */
			?>
			<div class="go-recent-tweets">
				<ul>
					<?php foreach( $tweets as $tweet ) : ?>
					<li>
						<p class="tweet-text">
							<?php echo wptexturize( $tweet['text'] ); ?>
						</p>
						<time class="tweet-meta">
							On <a href="<?php echo esc_url( $tweet['uri'] ); ?>" target="_blank" rel="noopener"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $tweet['created_at'] ) ); ?></a>
						<time>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php
		}

		echo $args['after_widget'];
	}

	/**
	 * Renders a fairly generic 'Create Widget' form that allows for a custom
	 * widget title (or no title at all).
	 */
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		} else {
			$title = __( 'Recent Tweets', 'go-recent-tweets' );
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'go-recent-tweets' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php 
	}
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}
}

/**
 * 'Main' plugin class and settings
 */
class GO_Recent_Tweets {
	/**
	 * Registers actions/hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'create_menu' ) );
		add_action( 'widgets_init', array( $this, 'load_widget' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Loads plugin textdomain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'go-recent-tweets', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Actually registers the widget defined earlier.
	 */
	public function load_widget() {
		register_widget( 'GO_Recent_Tweets_Widget' );
	}

	/**
	 * Registers the plugin settings page.
	 */
	public function create_menu() {
		add_options_page(
			__( 'Recent Tweets', 'go-recent-tweets' ),
			__( 'Recent Tweets', 'go-recent-tweets' ),
			'manage_options',
			'go-recent-tweets-settings',
			array( $this, 'settings_page' )
		);
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function register_settings() {
		register_setting( 'go-recent-tweets-settings-group', 'go_twitter_api_oauth_access_token' );
		register_setting( 'go-recent-tweets-settings-group', 'go_twitter_api_oauth_access_token_secret' );
		register_setting( 'go-recent-tweets-settings-group', 'go_twitter_api_consumer_key' );
		register_setting( 'go-recent-tweets-settings-group', 'go_twitter_api_consumer_secret' );
		register_setting( 'go-recent-tweets-settings-group', 'go_twitter_username' );
		register_setting( 'go-recent-tweets-settings-group', 'go_twitter_max_no' );
	}

	/**
	 * Renders the plugin options form.
	 */
	public function settings_page() {
		?>
		<div class="wrap">
			<h1><?php _e( 'Recent Tweets', 'go-recent-tweets' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'go-recent-tweets-settings-group' ); ?>
				<?php do_settings_sections( 'go-recent-tweets-settings-group' ); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e( 'Twitter OAuth Access Token', 'go-recent-tweets' ); ?></th>
						<td><input type="text" name="go_twitter_api_oauth_access_token" value="<?php echo esc_attr( get_option( 'go_twitter_api_oauth_access_token' ) ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Twitter OAuth Access Token Secret', 'go-recent-tweets' ); ?></th>
						<td><input type="text" name="go_twitter_api_oauth_access_token_secret" value="<?php echo esc_attr( get_option( 'go_twitter_api_oauth_access_token_secret' ) ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Twitter API Consumer Key', 'go-recent-tweets' ); ?></th>
						<td><input type="text" name="go_twitter_api_consumer_key" value="<?php echo esc_attr( get_option( 'go_twitter_api_consumer_key' ) ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Twitter API Consumer Secret', 'go-recent-tweets' ); ?></th>
						<td><input type="text" name="go_twitter_api_consumer_secret" value="<?php echo esc_attr( get_option( 'go_twitter_api_consumer_secret' ) ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Twitter username', 'go-recent-tweets' ); ?></th>
						<td><input type="text" name="go_twitter_username" value="<?php echo esc_attr( get_option( 'go_twitter_username' ) ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Number of tweets to display', 'go-recent-tweets' ); ?></th>
						<td><input type="text" name="go_twitter_max_no" value="<?php echo esc_attr( get_option( 'go_twitter_max_no' ) ); ?>" /></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Updates the tweet list by calling the Twitter API.
	 */
	public static function load_tweets() {
		require_once dirname( __FILE__ ) . '/vendor/twitter-api-php/TwitterAPIExchange.php';
		/*
		 * Get Twitter API settings
		 */
		$settings = array(
			'oauth_access_token' => get_option( 'go_twitter_api_oauth_access_token' ),
			'oauth_access_token_secret' => get_option( 'go_twitter_api_oauth_access_token_secret' ),
			'consumer_key' => get_option( 'go_twitter_api_consumer_key' ),
			'consumer_secret' => get_option( 'go_twitter_api_consumer_secret' ),
		);

		$twitter_username = urlencode( get_option( 'go_twitter_username' ) );
		$max_no = (int) get_option( 'go_twitter_max_no' );
		$num_tweets = $max_no + 20; // So as to grab a couple extra

		$url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
		$getfield = '?screen_name=' . $twitter_username . '&count=' . $num_tweets . '&include_rts=1';
		$requestMethod = 'GET';

		$twitter = new TwitterAPIExchange( $settings );
		$response = $twitter->setGetfield( $getfield )
		                    ->buildOauth( $url, $requestMethod )
		                    ->performRequest();

		$json = @json_decode( $response, true );

		if ( ! empty( $json ) && is_array( $json ) ) {
			$tweets = array();

			foreach ( $json as $tweet ) {
				$tweets[] = array(
					'text' => sanitize_text_field( $tweet['text'] ),
					'created_at' => get_date_from_gmt( date( 'Y-m-d H:i:s', strtotime( $tweet['created_at'] ) ), get_option( 'date_format' ) ),
					'uri' => 'https://twitter.com/' . $tweet['user']['screen_name'] . '/status/' . $tweet['id_str'],
				);

				if ( count( $tweets ) >= $max_no ) {
					break; // Break out of the loop
				}
			}

			set_transient( 'go_recent_tweets', $tweets, 12 * HOUR_IN_SECONDS ); // Store for 12 hours

			return $tweets;
		}

		return false;
	}
}

$go_recent_tweets = new GO_Recent_Tweets();
