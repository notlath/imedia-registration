<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IMF_Frontend {


	public function __construct() {
		add_shortcode( 'imedia_form', array( $this, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function enqueue_assets() {
		wp_enqueue_style( 'imf-frontend-css', Imedia_Forms::plugin_url() . 'resources/assets/css/style.css', array(), Imedia_Forms::VERSION );
		wp_enqueue_script( 'imf-frontend-js', Imedia_Forms::plugin_url() . 'resources/assets/js/script.js', array(), Imedia_Forms::VERSION, true );
	}

	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'       => 0,
				'category' => '',
			),
			$atts
		);
		$id   = intval( $atts['id'] );
		if ( ! $id ) {
			return '<p>Invalid form ID.</p>';
		}

		$post = get_post( $id );
		if ( ! $post || $post->post_type !== Imedia_Forms::CPT ) {
			return '<p>Form not found.</p>';
		}

		$status = get_post_meta( $id, Imedia_Forms::META_STATUS, true );
		if ( $status === 'inactive' ) {
			return '';
		}

		$fields = json_decode( get_post_meta( $id, Imedia_Forms::META_FIELDS, true ), true );
		if ( ! is_array( $fields ) || empty( $fields ) ) {
			return '<p>This form has no fields.</p>';
		}

		$api_url    = get_post_meta( $id, Imedia_Forms::META_API_URL, true );
		$appearance = imf_get_appearance_defaults( $id );

		$wp_api_url = rest_url( 'imedia-forms/v1/submit' );

		$submit_style = sprintf(
			'background-color:%s; color:%s; width:%s; border:none;',
			esc_attr( $appearance['submit_bg_color'] ),
			esc_attr( $appearance['submit_text_color'] ),
			$appearance['submit_width'] === 'full' ? '100%' : 'auto'
		);

		$site_key      = get_option( 'imf_recaptcha_site_key', '' );
		$use_recaptcha = ( ! empty( $appearance['enable_recaptcha'] ) && $appearance['enable_recaptcha'] === '1' && ! empty( $site_key ) );
		$use_honeypot  = ( ! empty( $appearance['enable_honeypot'] ) && $appearance['enable_honeypot'] === '1' );

		if ( $use_recaptcha ) {
			wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null, true );
		}

		ob_start();
		?>
		<div class="imf-form-wrap imf-form-card" data-form-id="<?php echo esc_attr( $id ); ?>" data-api-url="<?php echo esc_attr( $api_url ); ?>" data-wp-api-url="<?php echo esc_url( $wp_api_url ); ?>">
			<form class="imf-frontend-form" novalidate>
				<h3 class="imf-form-title" style="color:<?php echo esc_attr( $appearance['title_color'] ); ?>;"><?php echo esc_html( $post->post_title ); ?></h3>
				<div class="imf-form-fields">
					<?php if ( $use_honeypot ) : ?>
						<div style="display:none !important;" aria-hidden="true">
							<input type="text" name="imf_hp_email" tabindex="-1" autocomplete="off" />
						</div>
					<?php endif; ?>
					<?php foreach ( $fields as $field ) : ?>
						<?php echo $this->render_frontend_field( $field, $atts['category'] ); ?>
					<?php endforeach; ?>
				</div>
				<div class="imf-form-submit" style="text-align:<?php echo esc_attr( $appearance['submit_alignment'] ); ?>;">
					<?php if ( $use_recaptcha ) : ?>
						<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $site_key ); ?>" style="margin-bottom:15px; display:inline-block; text-align:left;"></div><br>
					<?php endif; ?>
					<button type="submit" class="imf-submit-btn" style="<?php echo $submit_style; ?>">Submit</button>
				</div>
				<div class="imf-form-message" style="display:none;"></div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	private function render_frontend_field( $field, $category = '' ) {
		$t       = $field['type'];
		$n       = $field['name'] ?? $field['id'];
		$l       = esc_html( $field['label'] );
		$req     = ! empty( $field['required'] ) ? 'required' : '';
		$reqMark = ! empty( $field['required'] ) ? '<span class="imf-required">*</span>' : '';
		$w       = $field['width'] ?? '100';
		$ph      = esc_attr( $field['placeholder'] ?? '' );
		$v       = $field['validation'] ?? array();
		$cc      = ! empty( $field['custom_class'] ) ? ' ' . esc_attr( $field['custom_class'] ) : '';

		if ( $t === 'hidden' ) {
			return '<input type="hidden" name="' . esc_attr( $n ) . '" value="' . esc_attr( $field['default_value'] ?? '' ) . '" />';
		}

		$data_attrs = '';
		if ( ! empty( $field['required'] ) ) {
			$data_attrs .= ' data-required="1"';
		}
		if ( ! empty( $v['min_length'] ) ) {
			$data_attrs .= ' data-minlength="' . esc_attr( $v['min_length'] ) . '"';
		}
		if ( ! empty( $v['max_length'] ) ) {
			$data_attrs .= ' data-maxlength="' . esc_attr( $v['max_length'] ) . '"';
		}
		if ( ! empty( $v['pattern'] ) ) {
			$data_attrs .= ' data-pattern="' . esc_attr( $v['pattern'] ) . '"';
		}
		if ( ! empty( $v['custom_error'] ) ) {
			$data_attrs .= ' data-error="' . esc_attr( $v['custom_error'] ) . '"';
		}

		$html = '<div class="imf-field imf-field-w' . esc_attr( $w ) . $cc . '"' . $data_attrs . '>';

		if ( $t === 'section' ) {
			$html .= '<div class="imf-section-divider"><h4>' . $l . '</h4>';
			if ( ! empty( $field['default_value'] ) ) {
				$html .= '<p>' . esc_html( $field['default_value'] ) . '</p>';
			}
			$html .= '</div></div>';
			return $html;
		}

		$html .= '<label>' . $l . ' ' . $reqMark . '</label>';

		switch ( $t ) {
			case 'text':
			case 'phone':
				$inputType = $t === 'phone' ? 'tel' : 'text';
				$html     .= '<input type="' . $inputType . '" name="' . esc_attr( $n ) . '" placeholder="' . $ph . '" ' . $req . ' />';
				break;
			case 'number':
				$html .= '<input type="number" name="' . esc_attr( $n ) . '" placeholder="' . $ph . '" ' . $req . ' />';
				break;
			case 'textarea':
				$rows  = esc_attr( $field['rows'] ?? '3' );
				$html .= '<textarea name="' . esc_attr( $n ) . '" rows="' . $rows . '" placeholder="' . $ph . '" ' . $req . '></textarea>';
				break;
			case 'email':
				if ( ! empty( $field['confirm_email'] ) ) {
					$html .= '<div class="imf-field-row"><div class="imf-field-half"><input type="email" name="' . esc_attr( $n ) . '" placeholder="' . ( $ph ?: 'Enter Email' ) . '" ' . $req . ' /><span class="imf-sub-label">Enter Email</span></div>';
					$html .= '<div class="imf-field-half"><input type="email" name="' . esc_attr( $n ) . '_confirm" placeholder="Confirm Email" ' . $req . ' /><span class="imf-sub-label">Confirm Email</span></div></div>';
				} else {
					$html .= '<input type="email" name="' . esc_attr( $n ) . '" placeholder="' . $ph . '" ' . $req . ' />';
				}
				break;
			case 'date':
				$date_type = $field['date_input_type'] ?? 'date_picker';
				if ( $date_type === 'date_field' ) {
					$html .= '<div class="imf-date-fields">';
					$html .= '<div class="imf-date-part"><input type="text" name="' . esc_attr( $n ) . '_month" placeholder="MM" maxlength="2" inputmode="numeric" ' . $req . ' /><span class="imf-sub-label">Month</span></div>';
					$html .= '<span class="imf-date-sep">/</span>';
					$html .= '<div class="imf-date-part"><input type="text" name="' . esc_attr( $n ) . '_day" placeholder="DD" maxlength="2" inputmode="numeric" ' . $req . ' /><span class="imf-sub-label">Day</span></div>';
					$html .= '<span class="imf-date-sep">/</span>';
					$html .= '<div class="imf-date-part imf-date-year"><input type="text" name="' . esc_attr( $n ) . '_year" placeholder="YYYY" maxlength="4" inputmode="numeric" ' . $req . ' /><span class="imf-sub-label">Year</span></div>';
					$html .= '</div>';
				} elseif ( $date_type === 'date_dropdown' ) {
					$months = array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );
					$html  .= '<div class="imf-date-dropdowns">';
					$html  .= '<div class="imf-date-dd"><select name="' . esc_attr( $n ) . '_month" ' . $req . '><option value="">Month</option>';
					foreach ( $months as $i => $m ) {
						$html .= '<option value="' . ( $i + 1 ) . '">' . $m . '</option>';
					}
					$html .= '</select><span class="imf-sub-label">Month</span></div>';
					$html .= '<div class="imf-date-dd"><select name="' . esc_attr( $n ) . '_day" ' . $req . '><option value="">Day</option>';
					for ( $d = 1; $d <= 31; $d++ ) {
						$html .= '<option value="' . $d . '">' . $d . '</option>';
					}
					$html   .= '</select><span class="imf-sub-label">Day</span></div>';
					$html   .= '<div class="imf-date-dd"><select name="' . esc_attr( $n ) . '_year" ' . $req . '><option value="">Year</option>';
					$curYear = (int) wp_date( 'Y' );
					for ( $y = $curYear; $y >= $curYear - 100; $y-- ) {
						$html .= '<option value="' . $y . '">' . $y . '</option>';
					}
					$html .= '</select><span class="imf-sub-label">Year</span></div>';
					$html .= '</div>';
				} else {
					$html .= '<div class="imf-datepicker-wrap">';
					$html .= '<input type="text" class="imf-datepicker-display" placeholder="' . ( $ph ?: 'mm/dd/yyyy' ) . '" readonly ' . $req . ' />';
					$html .= '<input type="date" class="imf-datepicker-hidden" name="' . esc_attr( $n ) . '" tabindex="-1" />';
					$html .= '<button type="button" class="imf-datepicker-btn" aria-label="Open calendar"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></button>';
					$html .= '</div>';
				}
				break;
			case 'time':
				$html .= '<input type="time" name="' . esc_attr( $n ) . '" ' . $req . ' />';
				break;
			case 'name':
				$html .= '<div class="imf-field-row"><div class="imf-field-half"><input type="text" name="' . esc_attr( $n ) . '_first" placeholder="First Name" ' . $req . ' /><span class="imf-sub-label">First</span></div>';
				$html .= '<div class="imf-field-half"><input type="text" name="' . esc_attr( $n ) . '_last" placeholder="Last Name" ' . $req . ' /><span class="imf-sub-label">Last</span></div></div>';
				break;
			case 'address':
				$html .= '<div class="imf-address-grid">';
				$html .= '<div class="imf-addr-full"><input type="text" name="' . esc_attr( $n ) . '_street" ' . $req . ' /><span class="imf-sub-label">Street Address</span></div>';
				$html .= '<div class="imf-addr-full"><input type="text" name="' . esc_attr( $n ) . '_street2" /><span class="imf-sub-label">Address Line 2</span></div>';
				$html .= '<div class="imf-addr-half"><input type="text" name="' . esc_attr( $n ) . '_city" ' . $req . ' /><span class="imf-sub-label">City</span></div>';
				$html .= '<div class="imf-addr-half"><input type="text" name="' . esc_attr( $n ) . '_state" /><span class="imf-sub-label">State / Province</span></div>';
				$html .= '<div class="imf-addr-half"><input type="text" name="' . esc_attr( $n ) . '_zip" /><span class="imf-sub-label">ZIP / Postal Code</span></div>';
				$html .= '<div class="imf-addr-half"><input type="text" name="' . esc_attr( $n ) . '_country" /><span class="imf-sub-label">Country</span></div>';
				$html .= '</div>';
				break;
			case 'select':
			case 'multiselect':
				$opts  = ! empty( $field['options'] ) ? preg_split( '/\r?\n/', $field['options'] ) : array();
				$multi = $t === 'multiselect' ? ' multiple' : '';
				$html .= '<select name="' . esc_attr( $n ) . ( $t === 'multiselect' ? '[]' : '' ) . '"' . $multi . ' ' . $req . '>';
				if ( $t !== 'multiselect' ) {
					$html .= '<option value="">' . esc_html( $field['placeholder'] ?? 'Please select' ) . '</option>';
				}
				$inGroup         = false;
				$currentCategory = '';
				foreach ( $opts as $o ) {
					$o = trim( $o );
					if ( empty( $o ) ) {
						continue;
					}

					if ( strpos( $o, '@' ) === 0 ) {
						$currentCategory = trim( substr( $o, 1 ) );
						continue;
					}

					if ( ! empty( $category ) && $currentCategory !== '' && $currentCategory !== $category ) {
						continue;
					}

					if ( strpos( $o, '#' ) === 0 ) {
						if ( $inGroup ) {
							$html .= '</optgroup>';
						}
						$html   .= '<optgroup label="' . esc_attr( substr( $o, 1 ) ) . '">';
						$inGroup = true;
					} else {
						$html .= '<option value="' . esc_attr( $o ) . '">' . esc_html( $o ) . '</option>';
					}
				}
				if ( $inGroup ) {
					$html .= '</optgroup>';
				}
				$html .= '</select>';
				break;
			case 'radio':
			case 'checkbox':
			case 'multiple_choice':
				$typeAttr   = ( $t === 'radio' ) ? 'radio' : 'checkbox';
				$nameSuffix = ( $typeAttr === 'checkbox' ) ? '[]' : '';
				$opts       = ! empty( $field['options'] ) ? preg_split( '/\r?\n/', $field['options'] ) : array();
				$html      .= '<div class="imf-choices">';
				foreach ( $opts as $o ) {
					$o = trim( $o );
					if ( empty( $o ) ) {
						continue;
					}
					if ( strpos( $o, '#' ) === 0 ) {
						$html .= '<div class="imf-choice-group">' . esc_html( substr( $o, 1 ) ) . '</div>';
					} else {
						$html .= '<label class="imf-choice"><input type="' . $typeAttr . '" name="' . esc_attr( $n ) . $nameSuffix . '" value="' . esc_attr( $o ) . '" /> ' . esc_html( $o ) . '</label>';
					}
				}
				$html .= '</div>';
				break;
			case 'file':
				$accept = ! empty( $field['accepted_formats'] ) ? ' accept="' . esc_attr( $field['accepted_formats'] ) . '"' : '';
				$html  .= '<input type="file" name="' . esc_attr( $n ) . '"' . $accept . ' ' . $req . ' />';
				break;
		}

		$html .= '</div>';
		return $html;
	}
}
