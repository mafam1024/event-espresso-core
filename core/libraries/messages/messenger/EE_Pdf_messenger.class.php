<?php
/**
 * This contains the class for the EE PDF messenger.
 *
 * @since 4.5.0
 * @package Event Espresso
 * @subpackage messages
 */
if (!defined('EVENT_ESPRESSO_VERSION'))
    exit('NO direct script access allowed');

/**
 *
 * EE_Pdf_messenger class
 *
 *
 * @since 4.5.0
 *
 * @package            Event Espresso
 * @subpackage        messages
 * @author            Darren Ethier
 */
class EE_Pdf_messenger extends EE_messenger
{


    /**
     * The following are the properties that this messenger requires for generating pdf
     */

    /**
     * This is the pdf body generated by the template via the message type.
     *
     * @var string
     */
    protected $_content;


    /**
     * This is for the page title that gets displayed.  This will end up being the filename for the generated pdf.
     *
     * @var string
     */
    protected $_subject;


    /**
     * @return EE_Pdf_messenger
     */
    public function __construct()
    {
        //set properties
        $this->name = 'pdf';
        $this->description = __('This messenger is used for generating a pdf version of the message.', 'event_espresso');
        $this->label = array(
            'singular' => __('PDF', 'event_espresso'),
            'plural' => __('PDFs', 'event_espresso')
        );
        $this->activate_on_install = TRUE;

        parent::__construct();
    }


    /**
     * PDF Messenger desires execution immediately.
     * @see  parent::send_now() for documentation.
     * @since  4.9.0
     * @return bool
     */
    public function send_now()
    {
        return true;
    }


    /**
     * HTML Messenger allows an empty to field.
     * @see parent::allow_empty_to_field() for documentation
     * @since  4.9.0
     * @return bool
     */
    public function allow_empty_to_field()
    {
        return true;
    }


    /**
     * @see abstract declaration in EE_messenger for details.
     */
    protected function _set_admin_pages()
    {
        $this->admin_registered_pages = array('events_edit' => false);
    }


    /**
     * @see abstract declaration in EE_messenger for details.
     */
    protected function _set_valid_shortcodes()
    {
        $this->_valid_shortcodes = array();
    }


    /**
     * @see abstract declaration in EE_messenger for details.
     */
    protected function _set_validator_config()
    {
        $this->_validator_config = array(
            'subject' => array(
                'shortcodes' => array('recipient_details', 'organization', 'event', 'ticket', 'venue', 'primary_registration_details', 'event_author', 'email', 'event_meta', 'recipient_list', 'transaction', 'datetime_list', 'datetime')
            ),
            'content' => array(
                'shortcodes' => array('recipient_details', 'organization', 'event', 'ticket', 'venue', 'primary_registration_details', 'event_author', 'email', 'event_meta', 'recipient_list', 'transaction', 'datetime_list', 'datetime')
            ),
            'attendee_list' => array(
                'shortcodes' => array('attendee', 'event_list', 'ticket_list'),
                'required' => array('[ATTENDEE_LIST]')
            ),
            'event_list' => array(
                'shortcodes' => array('event', 'attendee_list', 'ticket_list', 'venue', 'datetime_list', 'attendee', 'primary_registration_details', 'primary_registration_list', 'event_author', 'recipient_details', 'recipient_list'),
                'required' => array('[EVENT_LIST]')
            ),
            'ticket_list' => array(
                'shortcodes' => array('event_list', 'attendee_list', 'ticket', 'datetime_list', 'primary_registration_details', 'recipient_details'),
                'required' => array('[TICKET_LIST]')
            ),
            'datetime_list' => array(
                'shortcodes' => array('datetime'),
                'required' => array('[DATETIME_LIST]')
            ),
        );
    }


    /**
     * Takes care of enqueuing any necessary scripts or styles for the page.  A do_action() so message types using this messenger can add their own js.
     *
     * @return void.
     */
    public function enqueue_scripts_styles()
    {
        parent::enqueue_scripts_styles();
        do_action('AHEE__EE_Pdf_messenger__enqueue_scripts_styles');
    }


    /**
     * _set_template_fields
     * This sets up the fields that a messenger requires for the message to go out.
     *
     * @access  protected
     * @return void
     */
    protected function _set_template_fields()
    {
        // any extra template fields that are NOT used by the messenger but will get used by a messenger field for shortcode replacement get added to the 'extra' key in an associated array indexed by the messenger field they relate to.  This is important for the Messages_admin to know what fields to display to the user.  Also, notice that the "values" are equal to the field type that messages admin will use to know what kind of field to display. The values ALSO have one index labeled "shortcode".  the values in that array indicate which ACTUAL SHORTCODE (i.e. [SHORTCODE]) is required in order for this extra field to be displayed.  If the required shortcode isn't part of the shortcodes array then the field is not needed and will not be displayed/parsed.
        $this->_template_fields = array(
            'subject' => array(
                'input' => 'text',
                'label' => __('Page Title', 'event_espresso'),
                'type' => 'string',
                'required' => TRUE,
                'validation' => TRUE,
                'css_class' => 'large-text',
                'format' => '%s'
            ),
            'content' => '', //left empty b/c it is in the "extra array" but messenger still needs needs to know this is a field.
            'extra' => array(
                'content' => array(
                    'main' => array(
                        'input' => 'wp_editor',
                        'label' => __('Main Content', 'event_espresso'),
                        'type' => 'string',
                        'required' => TRUE,
                        'validation' => TRUE,
                        'format' => '%s',
                        'rows' => '15'
                    ),
                    'event_list' => array(
                        'input' => 'wp_editor',
                        'label' => '[EVENT_LIST]',
                        'type' => 'string',
                        'required' => TRUE,
                        'validation' => TRUE,
                        'format' => '%s',
                        'rows' => '15',
                        'shortcodes_required' => array('[EVENT_LIST]')
                    ),
                    'attendee_list' => array(
                        'input' => 'textarea',
                        'label' => '[ATTENDEE_LIST]',
                        'type' => 'string',
                        'required' => TRUE,
                        'validation' => TRUE,
                        'format' => '%s',
                        'css_class' => 'large-text',
                        'rows' => '5',
                        'shortcodes_required' => array('[ATTENDEE_LIST]')
                    ),
                    'ticket_list' => array(
                        'input' => 'textarea',
                        'label' => '[TICKET_LIST]',
                        'type' => 'string',
                        'required' => TRUE,
                        'validation' => TRUE,
                        'format' => '%s',
                        'css_class' => 'large-text',
                        'rows' => '10',
                        'shortcodes_required' => array('[TICKET_LIST]')
                    ),
                    'datetime_list' => array(
                        'input' => 'textarea',
                        'label' => '[DATETIME_LIST]',
                        'type' => 'string',
                        'required' => TRUE,
                        'validation' => TRUE,
                        'format' => '%s',
                        'css_class' => 'large-text',
                        'rows' => '10',
                        'shortcodes_required' => array('[DATETIME_LIST]')
                    )
                )
            )
        );
    }


    /**
     * @see definition of this method in parent
     *
     * @since 4.5.0
     *
     */
    protected function _set_default_message_types()
    {
        //note currently PDF is only a secondary messenger so it never has any associated message types.
        $this->_default_message_types = array();
    }


    /**
     * @see definition of this method in parent
     *
     * @since 4.5.0
     */
    protected function _set_valid_message_types()
    {
        $this->_valid_message_types = array();
    }


    /**
     * Generates html version of the message content and then sends it to the pdf generator.
     *
     *
     * @since 4.5.0
     *
     * @return string.
     */
    protected function _send_message()
    {
        $this->_template_args = array(
            'page_title' => stripslashes($this->_subject),
            'base_css' => $this->get_variation($this->_tmp_pack, $this->_incoming_message_type->name, TRUE, 'base', $this->_variation),
            'print_css' => $this->get_variation($this->_tmp_pack, $this->_incoming_message_type->name, TRUE, 'print', $this->_variation),
            'main_css' => $this->get_variation($this->_tmp_pack, $this->_incoming_message_type->name, TRUE, 'main', $this->_variation),
            'extra_css' => EE_LIBRARIES_URL . 'messages/defaults/default/variations/pdf_base_default.css',
            'main_body' => apply_filters('FHEE__EE_Pdf_messenger___send_message__main_body', wpautop(stripslashes_deep($this->_content)), $this->_content)
        );
        $this->_deregister_wp_hooks();
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts_styles'));
        $content = $this->_get_main_template();
//		die( $content );
        $this->_do_pdf($content);
        exit(0);
    }


    /**
     * The purpose of this function is to de register all actions hooked into wp_head and wp_footer so that it doesn't interfere with our templates.  If users want to add any custom styles or scripts they must use the AHEE__EE_Pdf_messenger__enqueue_scripts_styles hook.
     *
     * @since 4.5.0
     *
     * @return void
     */
    protected function _deregister_wp_hooks()
    {
        remove_all_actions('wp_head');
        remove_all_actions('wp_footer');
        remove_all_actions('wp_print_footer_scripts');
        remove_all_actions('wp_enqueue_scripts');
        global $wp_scripts, $wp_styles;
        $wp_scripts = $wp_styles = array();

        //just add back in wp_enqueue_scripts and wp_print_footer_scripts cause that's all we want to load.
        add_action('wp_head', 'wp_enqueue_scripts');
        add_action('wp_footer', 'wp_print_footer_scripts');
        add_action('wp_print_footer_scripts', '_wp_footer_scripts');
    }


    /**
     * Overwrite parent _get_main_template for pdf purposes.
     *
     * @since  4.5.0
     *
     * @param bool $preview
     * @return string
     */
    protected function _get_main_template($preview = FALSE)
    {
        $wrapper_template = $this->_tmp_pack->get_wrapper('html', 'main');
        //add message type to template_args
        $this->_template_args['message_type'] = $this->_incoming_message_type;
        return EEH_Template::display_template($wrapper_template, $this->_template_args, TRUE);
    }


    /**
     * This takes care of loading the dompdf library and generating the actual pdf
     *
     * @param string $content This is the generated html content being converted into a pdf.
     *
     * @return void
     */
    protected function _do_pdf($content = '')
    {
        $invoice_name = $this->_subject;

        //only load dompdf if nobody else has yet...
        if (!defined('DOMPDF_DIR')) {
            define('DOMPDF_ENABLE_REMOTE', TRUE);
            define('DOMPDF_ENABLE_JAVASCRIPT', FALSE);
            define('DOMPDF_ENABLE_CSS_FLOAT', TRUE);
            require_once(EE_THIRD_PARTY . 'dompdf/dompdf_config.inc.php');
        }
        $dompdf = new DOMPDF();
        if (defined('DOMPDF_DEFAULT_PAPER_SIZE')) {
            $dompdf->set_paper(DOMPDF_DEFAULT_PAPER_SIZE);
        }
        //Remove all spaces between HTML tags
        $content = preg_replace('/>\s+</', '><', $content);
        $dompdf->load_html($content);
        $dompdf->render();
        //forcing the browser to open a download dialog.
        $dompdf->stream($invoice_name . ".pdf", array('Attachment' => TRUE));
    }


    /**
     * @return string
     */
    protected function _preview()
    {
        return $this->_send_message();
    }


    protected function _set_admin_settings_fields()
    {
    }
}
