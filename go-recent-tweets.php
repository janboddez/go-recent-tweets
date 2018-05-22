<?php
/**
 * Plugin Name: GO Recent Tweets Widget
 * Plugin URI: https://janboddez.be/
 * Description: Retrieve, cache and display your most recent tweets.
 * Author: Jan Boddez
 * Author URI: https://janboddez.be/
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: go-recent-tweets
 * Version: 0.1.0
 *
 * @author Jan Boddez [jan@janboddez.be]
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0
 * @package GO_Recent_Tweets
 */

/* Prevents this script from being loaded directly. */
defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'GO_Recent_Tweets_Widget' ) ) :
/**
 * Defines the widget.
 *
 * @since 0.1.0
 */
class GO_Recent_Tweets_Widget extends WP_Widget {
	/**
	 * Sets up the widget.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		parent::__construct(
			'go_recent_tweets_widget', 
			__( 'Recent Tweets', 'go-recent-tweets' ), 
			array( 'description' => __( 'Retrieve, cache (!) and display your most recent tweets.', 'go-recent-tweets' ) )
		);
	}

	/**
	 * Renders the actual widget.
	 *
	 * @since 0.1.0
	 * @param array $args Display arguments.
	 * @param array $instance The settings for the particular widget instance.
	 */
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] );
		echo $args['before_widget'];

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		/*
		 * Retrieves the tweet list stored previously. (And returns either an
		 * array or false.)
		 */
		$tweets = get_transient( 'go_recent_tweets' );

		if ( false === $tweets ) {
			$tweets = GO_Recent_Tweets::load_tweets();
		}

		if ( is_array( $tweets ) && isset( $tweets[0]['text'] ) ) {
			/* Loops through the list and outputs the actual tweets. */
			?>
			<div class="go-recent-tweets">
				<ul>
					<?php foreach( $tweets as $tweet ) : ?>
					<li>
						<div class="tweet-content">
							<?php echo $this->make_links_clickable( $tweet['text'] ); ?>
						</div>
						<div class="tweet-meta">
							<small><time><?php echo sprintf( __( 'On %s', 'go-recent-tweets' ), '<a href="' . esc_url( $tweet['uri'] ) .'" target="_blank" rel="noopener">' . date_i18n( get_option( 'date_format' ), strtotime( $tweet['created_at'] ) ) . '</a>' ); ?></time></small>
						</div>
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
	 *
	 * @since 0.1.0
	 * @param array $instance Current settings.
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

	/**
	 * Updates the widget settings.
	 *
	 * @since 0.1.0
	 * @param array $new_instance New settings for this instance, input by the user.
	 * @param array $old_instance Old settings for this instance.
	 * @return array|false Settings to save or false to cancel.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';

		return $instance;
	}

	/**
	 * Turns Twitter handles and in-tweet URIs into hyperlinks.
	 *
	 * @since 0.1.0
	 * @param string $text Text that may or may not contain Twitter handles and URLS.
	 * @return string Text containing actual HTML hyperlinks, if applicable.
	 */
	private function make_links_clickable( $text = '', $new_tab = true ) {
		$text = wptexturize( $text );
		$text = preg_replace( '/(^|[^a-z0-9_])@([a-z0-9_]+)/i', '$1<a href="https://twitter.com/$2" rel="nofollow">@$2</a>', $text );
		$text = make_clickable( $text ); // Will add 'rel="nofollow"' by default.

		if ( $new_tab ) {
			$text = preg_replace( '/(<a\b[^<>]*href=[\'"]?http[^<>]+)>/is', '$1 target="_blank">', $text );
			$text = str_replace( 'rel="nofollow"', 'rel="nofollow noopener"', $text );
		}

		return $text;
	}
}
endif;

if ( ! class_exists( 'GO_Recent_Tweets' ) ) :
/**
 * 'Main' plugin class and settings.
 */
class GO_Recent_Tweets {
	/**
	 * Registers actions.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'create_menu' ) );
		add_action( 'widgets_init', array( $this, 'load_widget' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts' ) );
		add_action( 'wp_ajax_go_recent_tweets_clear_cache', array( $this, 'delete_tweets' ) );
	}

	/**
	 * Loads the plugin textdomain, enabling translations.
	 *
	 * @since 0.1.0
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'go-recent-tweets', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Actually tells WordPress about the widget defined earlier.
	 *
	 * @since 0.1.0
	 */
	public function load_widget() {
		register_widget( 'GO_Recent_Tweets_Widget' );
	}

	/**
	 * Registers the plugin settings page.
	 *
	 * @since 0.1.0
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

	/**
	 * Loads admin scripts.
	 *
	 * @since 0.1.0
	 */
	public function load_admin_scripts() {
		wp_enqueue_script( 'go-recent-tweets-admin', plugin_dir_url( __FILE__ ) . 'js/go-recent-tweets-admin.js', array( 'jquery' ) );
	}

	/**
	 * Registers the actual options.
	 *
	 * @since 0.1.0
	 */
	public function register_settings() {
		register_setting( 'go-recent-tweets-settings-group', 'go_recent_tweets_settings', array( 'sanitize_callback' => array( $this, 'sanitize_settings' ) ) );
	}

	/**
	 * Sanitizes submitted options.
	 *
	 * @since 0.1.0
	 * @param array $settings Settings as input by the user.
	 * @return array Settings to save to the database.
	 */
	public function sanitize_settings( $settings ) {
		$options = get_option( 'go_recent_tweets_settings', array() );

		if ( isset( $settings['api_oauth_access_token'] ) ) {
			$options['api_oauth_access_token'] = sanitize_text_field( $settings['api_oauth_access_token'] );
		}

		if ( isset( $settings['api_oauth_access_token_secret'] ) ) {
			$options['api_oauth_access_token_secret'] = sanitize_text_field( $settings['api_oauth_access_token_secret'] );
		}

		if ( isset( $settings['api_consumer_key'] ) ) {
			$options['api_consumer_key'] = sanitize_text_field( $settings['api_consumer_key'] );
		}

		if ( isset( $settings['api_consumer_secret'] ) ) {
			$options['api_consumer_secret'] = sanitize_text_field( $settings['api_consumer_secret'] );
		}

		if ( isset( $settings['username'] ) ) {
			$options['username'] = sanitize_text_field( $settings['username'] );
		}

		if ( isset( $settings['max_no'] ) ) {
			$options['max_no'] = intval( $settings['max_no'] );
		}

		if ( isset( $settings['incl_rts'] ) ) {
			$options['incl_rts'] = intval( $settings['incl_rts'] );
		}

		return $options;
	}

	/**
	 * Echoes the plugin options form.
	 *
	 * @since 0.1.0
	 */
	public function settings_page() {
		?>
		<div class="wrap">
			<h1><?php _e( 'Recent Tweets', 'go-recent-tweets' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				$options = get_option( 'go_recent_tweets_settings', false );

				if ( false === $options ) {
					// Should this form get called before any settings were ever saved.
					$options = array(
						'api_oauth_access_token' => '',
						'api_oauth_access_token_secret' => '',
						'api_consumer_key' => '',
						'api_consumer_secret' => '',
						'username' => '',
						'max_no' => 10,
						'incl_rts' => '1',
					);
				}

				settings_fields( 'go-recent-tweets-settings-group' );
				do_settings_sections( 'go-recent-tweets-settings-group' );
				?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><label for="go_recent_tweets_settings[api_oauth_access_token]"><?php _e( 'Twitter OAuth Access Token', 'go-recent-tweets' ); ?></label></th>
						<td><input type="text" id="go_recent_tweets_settings[api_oauth_access_token]" name="go_recent_tweets_settings[api_oauth_access_token]" value="<?php echo esc_attr( $options['api_oauth_access_token'] ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="go_recent_tweets_settings[api_oauth_access_token_secret]"><?php _e( 'Twitter OAuth Access Token Secret', 'go-recent-tweets' ); ?></label></th>
						<td><input type="text" id="go_recent_tweets_settings[api_oauth_access_token_secret]" name="go_recent_tweets_settings[api_oauth_access_token_secret]" value="<?php echo esc_attr( $options['api_oauth_access_token_secret'] ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="go_recent_tweets_settings[api_consumer_key]"><?php _e( 'Twitter API Consumer Key', 'go-recent-tweets' ); ?></label></th>
						<td><input type="text" id="go_recent_tweets_settings[api_consumer_key]" name="go_recent_tweets_settings[api_consumer_key]" value="<?php echo esc_attr( $options['api_consumer_key'] ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="go_recent_tweets_settings[api_consumer_secret]"><?php _e( 'Twitter API Consumer Secret', 'go-recent-tweets' ); ?></label></th>
						<td><input type="text" id="go_recent_tweets_settings[api_consumer_secret]" name="go_recent_tweets_settings[api_consumer_secret]" value="<?php echo esc_attr( $options['api_consumer_secret'] ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="go_recent_tweets_settings[username]"><?php _e( 'Twitter username', 'go-recent-tweets' ); ?></label></th>
						<td><input type="text" id="go_recent_tweets_settings[username]" name="go_recent_tweets_settings[username]" value="<?php echo esc_attr( $options['username'] ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="go_recent_tweets_settings[max_no]"><?php _e( 'Number of tweets to display', 'go-recent-tweets' ); ?></label></th>
						<td><input type="text" id="go_recent_tweets_settings[max_no]" name="go_recent_tweets_settings[max_no]" value="<?php echo esc_attr( $options['max_no'] ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Include retweets?', 'go-recent-tweets' ); ?></th>
						<td>
							<label><input type="radio" name="go_recent_tweets_settings[incl_rts]" value="1" <?php checked( 1, $options['incl_rts'] ); ?>> <?php _e( 'Yes', 'go-recent-tweets' ); ?></label><br />
							<label><input type="radio" name="go_recent_tweets_settings[incl_rts]" value="0" <?php checked( 0, $options['incl_rts'] ); ?>> <?php _e( 'No', 'go-recent-tweets' ); ?></label>
						</td>
					</tr>
				</table>
				<p class="submit">
					<?php submit_button( __( 'Save Changes', 'go-recent-tweets' ), 'primary', 'submit', false ); ?>
					<input type="button" id="go-recent-tweets-clear-cache" class="button" value="<?php _e( 'Clear Cache', 'go-recent-tweets' ); ?>" />
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Deletes the saved tweet list.
	 *
	 * @since 0.1.0
	 */
	public function delete_tweets() {
		delete_transient( 'go_recent_tweets' );
		_e( 'Cache cleared!', 'go-recent-tweets' );
		wp_die();
	}

	/**
	 * Updates the list of tweets by calling the Twitter API.
	 *
	 * @since 0.1.0
	 * @return array|false An array of recent tweets or false on failure.
	 */
	public static function load_tweets() {
		require_once dirname( __FILE__ ) . '/vendor/twitter-api-php/TwitterAPIExchange.php';
		$options = get_option( 'go_recent_tweets_settings', false );

		if ( false === $options ) {
			// Should this method get called before any settings were ever saved.
			$options = array(
				'api_oauth_access_token' => '',
				'api_oauth_access_token_secret' => '',
				'api_consumer_key' => '',
				'api_consumer_secret' => '',
				'username' => '',
				'max_no' => 10,
				'incl_rts' => '1',
			);
		}

		$api_settings = array(
			'oauth_access_token' => $options['api_oauth_access_token'],
			'oauth_access_token_secret' => $options['api_oauth_access_token_secret'],
			'consumer_key' => $options['api_consumer_key'],
			'consumer_secret' => $options['api_consumer_secret'],
		);
		$num_tweets = (int) $options['max_no'] + 10; // So as to grab a couple extra.

		$url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
		$getfield = '?screen_name=' . urlencode( $options['username'] ) . '&count=' . (string) $num_tweets . '&include_rts=' . $options['incl_rts'];
		$requestMethod = 'GET';

		$twitter = new TwitterAPIExchange( $api_settings );
		$response = $twitter->setGetfield( $getfield )->buildOauth( $url, $requestMethod )->performRequest();
		$json = @json_decode( $response, true );

		if ( is_array( $json ) && ! isset( $json['errors'] ) ) {
			$tweets = array();

			foreach ( $json as $tweet ) {
				if ( isset( $tweet['text'] ) && isset( $tweet['created_at'] ) && isset( $tweet['id_str'] ) ) {
					$tweets[] = array(
						'text' => sanitize_text_field( $tweet['text'] ),
						'created_at' => get_date_from_gmt( date( 'Y-m-d H:i:s', strtotime( $tweet['created_at'] ) ), get_option( 'date_format' ) ),
						'uri' => 'https://twitter.com/' . $tweet['user']['screen_name'] . '/status/' . $tweet['id_str'],
					);

					if ( count( $tweets ) >= (int) $options['max_no'] ) {
						break; // Break out of the loop.
					}
				}
			}

			set_transient( 'go_recent_tweets', $tweets, 12 * HOUR_IN_SECONDS ); // Store for 12 hours.

			return $tweets;
		}

		if ( isset( $json['errors'][0]['message'] ) ) {
			self::error_log( $json['errors'][0]['message'] );
		}

		return false;
	}

	/**
	 * Logs error messages to the debug log file in the plugin folder, but only
	 * if WordPress debug logging is enabled.
	 *
	 * @since 0.1.0
	 * @param string $message The (error) message to be logged.
	 */
	public static function error_log( $message ) {
		if ( true === WP_DEBUG_LOG ) {
			error_log( date_i18n( 'Y-m-d H:i:s' ) . ' ' . $message . PHP_EOL, 3, dirname( __FILE__ ) . '/debug.log' );
		}
	}
}
endif;

$go_recent_tweets = new GO_Recent_Tweets();
