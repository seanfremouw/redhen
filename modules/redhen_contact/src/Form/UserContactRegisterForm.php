<?php

namespace Drupal\redhen_contact\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\user\RegisterForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\redhen_contact\Entity\Contact;
use Drupal\user\Entity\User;

/**
 * Class UserContactRegisterForm.
 */
class UserContactRegisterForm extends RegisterForm {

  /**
   * The Redhen Contact entity.
   *
   * @var \Drupal\redhen_contact\ContactInterface
   */
  private $redhenEntity;

  /**
   * Redhen settings factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $redhenConfig;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_contact_register_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return 'user_register_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function init(FormStateInterface $form_state) {
    $this->redhenConfig = $this->config('redhen_contact.settings');
    $redhen_entity_bundle = $this->redhenConfig->get('registration_type');
    $this->redhenEntity = $this->entityManager
      ->getStorage('redhen_contact')
      ->create(['type' => $redhen_entity_bundle]);
    parent::init($form_state);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function form(array $form, FormStateInterface $form_state) {
    // Add Redhen Contact form first so that the user display will override.
    $this->contactForm($form, $form_state);
    $form = parent::form($form, $form_state);
     return $form;
  }

  /**
   * Build a Redhen Contact form and add to current form.
   */
  public function contactForm(&$form, FormStateInterface &$form_state) {
    // Get Redhen Contact settings.
    // Check whether we should create a Contact on User registration.
    if ($this->redhenConfig->get('connect_users')) {

      // Get menu item to check for overridden Contact Type parameter, but only
      // when a user is registering from user/register.
      $url_exploded = array_slice(explode('/', $this->getRequest()->getRequestUri()), 1);
      if ($url_exploded[0] == 'user' && $url_exploded[1] == 'register' && isset($url_exploded[2])) {
        $contact_type = $url_exploded[2];
      }
      else {
        // If a parameter was not passed, use the default contact type.
        $contact_type = $this->redhenConfig->get('registration_type');
      }

      // If a valid contact type was found, embed fields from the Contact Type on
      // the user registration form.
      $types = redhen_contact_type_options_list();
      if (array_key_exists($contact_type, $types)) {
        $embed_key = 'redhen_contact_' . $contact_type;
        $embed_key = 'account';
        $this->embedContactForm($form, $form_state, $this->redhenConfig->get('registration_form'));
        // Hide the Contact email field, we will use the user mail field.
        //embed_key
        $form['email']['#access'] = FALSE;
      }
      else {
        drupal_set_message(t('Invalid RedHen contact type parameter.'));
      }
    }
  }

  public function embedContactForm(&$form, &$form_state, $form_mode = 'default') {

    // Place new Contact object in form_state - we use this if an existing Contact
    // is not found to link the user being created to.
    $form_state->set('redhen_contact', $this->redhenEntity);
    // Create form element to hold Contact fields.
    $embed_key = 'redhen_contact_' . $this->redhenEntity->getType();
    $embed_key = 'account';
    //$form[$embed_key]['#element_validate'][] = 'redhen_contact_user_update_validate';
    //$form[$embed_key]['#parents'][] = 'form_display_' . $contact->getType();
    $form['#element_validate'][] = 'redhen_contact_user_update_validate';
    $form['#parents'][] = 'form_display_' . $this->redhenEntity->getType();
//  $form[$embed_key] = [
//    '#type' => 'details',
//    '#title' => str_replace('!type', ContactType::load($contact->getType())->label(), '!type Contact information'),
//    '#tree' => TRUE,
//    '#parents' => ['form_display_' . $contact->getType()],
//    '#open' => TRUE,
//    '#element_validate' => ['redhen_contact_user_update_validate'],
//    // We would prefer to base this on weight of $form['account']['#weight'],
//    // but that value gets changed before the form renders to be "1".
//    '#weight' => 10,
//  ];

    // Add EntityFormDisplay object used to build an entity's form.
    if (!$form_mode) {
      $form_mode = 'default';
    }
    $form_state->set('form_display_' . $this->redhenEntity->getType(), EntityFormDisplay::collectRenderDisplay($this->redhenEntity, $form_mode));
    // Build the entity's form into the placeholder form element created above.
    $form_state
      ->get('form_display_' . $this->redhenEntity->getType())
      // Add fields to the placeholder form element created above.
      ->buildForm($this->redhenEntity, $form, $form_state);
    // Hide user linkage field when embedded.
    // embed_key
    $form['account']['uid']['#access'] = FALSE;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $this->validateContactForm($form, $form_state);

  }

  /**
   * Custom validate method to handle the Redhen Contact.
   */
  public function validateContactForm(array &$form, FormStateInterface $form_state) {
    // Load existing Contact by email address if one exists.
    $existing_contacts = Contact::loadByMail($form_state->getValue('mail'));
    // If no pre-existing Contact, use Contact created and put into the
    // form_state array when the user_registration form was loaded.
    $contact = $existing_contacts ? current($existing_contacts) : $form_state->get('redhen_contact');

    // Check whether we should update info of an existing Contact using info
    // provided on user_registration form.
    $update_existing = $this->redhenConfig->get('registration_update');

    // We have an existing contact, but it's of a different type.
    if ($existing_contacts && $contact->getType() !== $form_state->get('redhen_contact')->getType()) {
      $form_state->setError($form['account']['mail'], str_replace(['!type', '!email'], [$contact->getType(), $form_state->getValue('mail')], 'A Contact of type "!type" is already associated with the email address "!email".'));
    }

    // We don't want to update contacts, but found an existing match.
    if ($existing_contacts && !$update_existing) {
      $form_state->setError($form['account']['mail'], 'A contact already exists with that email address.');
    }

    // Existing contact is already linked to a user.
    if ($existing_contacts && !is_null($contact->getUser()) && $update_existing) {
      $form_state->setError($form['account']['mail'], 'A contact with that email address is already linked to a Drupal user.');
    }

    // Validate submitted field values and update Contact stored in form_state
    // to be our chosen Contact (i.e. pre-existing Contact with matching email
    // address or new Contact) with its field values updated from the values
    // supplied in the form.
    //_redhen_contact_user_submission_validate($form, $form_state, $contact);
    $this->buildContactEntity($form, $contact, $form_state);
    $form_display = $form_state->get('form_display_' . $this->redhenEntity->getType());
    $form_display->validateFormValues($contact, $form, $form_state);
    $contact->setValidationRequired(FALSE);
    $form_state->set('redhen_contact', $contact);

    $triggering_element = $form_state->getTriggeringElement();
    foreach($form_state->getErrors() as $name => $message) {
      // $name may be unknown in $form_state and
      // $form_state->setErrorByName($name, $message) may suppress the error message.
      $form_state->setError($triggering_element, $message);
    }
  }

  /**
   * Builds an updated entity object based upon the submitted form values.
   *
   * @param array $entity_form
   *   The entity form.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function buildContactEntity(array $entity_form, ContentEntityInterface $entity, FormStateInterface $form_state) {
    $form_display = $form_state->get('form_display_' . $entity->getType());
    $form_display->extractFormValues($entity, $entity_form, $form_state);
    // Invoke all specified builders for copying form values to entity fields.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->submitContactForm($form, $form_state);
  }

  /**
   * Custom submit method to handle the Redhen Contact.
   */
  public function submitContactForm(array &$form, FormStateInterface $form_state) {
    // Load Contact
    $contact = $form_state->get('redhen_contact');

    $form_state->cleanValues();
    $this->buildContactEntity($form, $contact, $form_state);

    $this->redhenEntity = $contact;

    // Update form_state Contact for later processing.
    $form_state->set('redhen_contact', $contact);
  }

  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    // Connect Drupal user to Redhen Contact.
    // We know this should happen on submit without checking anything because
    // we only embed redhen_contact fields on the user_registration form if
    // redhen_contact.settings.connect_users is TRUE.
    // See redhen_contact_form_user_register_form_alter().
    $contact = $form_state->get('redhen_contact');
    $contact->setUserId($form_state->getFormObject()->getEntity()->id());
    // Set Contact's email address to that of the new User.
    $contact->setEmail($form_state->getValue('mail'));

    // Save Contact associated with the user being created.
    $contact->save();

    // Add message that new User was linked to a Contact.
    $message = t('User has been linked to the contact %name.',
      [
        '%name' => $contact->label(),
      ]
    );
    // Only display this message to CRM admins to avoid confusion.
    $user = $this->currentUser();
    if ($user->hasPermission('administer contact entities')) {
      drupal_set_message($message);
    }
  }

}
