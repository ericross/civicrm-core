<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Extension_Manager_PaymentTest extends CiviUnitTestCase {
  function setUp() {
    parent::setUp();
    if (class_exists('test_extension_manager_paymenttest')) {
      test_extension_manager_paymenttest::$counts = array();
    }
    $this->system = new CRM_Extension_System(array(
      'extensionsDir' => '',
      'extensionsURL' => '',
    ));
    $this->quickCleanup(array('civicrm_payment_processor'));
  }

  function tearDown() {
    parent::tearDown();
    $this->system = NULL;
    $this->quickCleanup(array('civicrm_payment_processor'));
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest"');
  }

  /**
   * Install an extension with a valid type name
   */
  function testInstallDisableUninstall() {
    $manager = $this->system->getManager();
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest"');
    $manager->install(array('test.extension.manager.paymenttest'));
    $this->assertEquals(1, test_extension_manager_paymenttest::$counts['install']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest" AND is_active = 1');

    $manager->disable(array('test.extension.manager.paymenttest'));
    $this->assertEquals(1, test_extension_manager_paymenttest::$counts['disable']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest" AND is_active = 0');

    $manager->uninstall(array('test.extension.manager.paymenttest'));
    $this->assertEquals(1, test_extension_manager_paymenttest::$counts['uninstall']);
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest"');
  }

  /**
   * Install an extension with a valid type name
   */
  function testInstallDisableEnable() {
    $manager = $this->system->getManager();
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest"');
    
    $manager->install(array('test.extension.manager.paymenttest'));
    $this->assertEquals(1, test_extension_manager_paymenttest::$counts['install']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest" AND is_active = 1');

    $manager->disable(array('test.extension.manager.paymenttest'));
    $this->assertEquals(1, test_extension_manager_paymenttest::$counts['disable']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest" AND is_active = 0');

    $manager->enable(array('test.extension.manager.paymenttest'));
    $this->assertEquals(1, test_extension_manager_paymenttest::$counts['enable']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest" AND is_active = 1');
  }
  /**
   * Install an extension and create a payment processor which uses it.
   * Attempts to uninstall fail
   */
  function testInstall_Add_FailUninstall() {
    $manager = $this->system->getManager();
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest"');

    $manager->install(array('test.extension.manager.paymenttest'));
    $this->assertEquals(1, test_extension_manager_paymenttest::$counts['install']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest" AND is_active = 1');
    $payment_processor_type_id = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_payment_processor_type  WHERE class_name = "test.extension.manager.paymenttest"');

    $ppDAO = CRM_Financial_BAO_PaymentProcessor::create(array(
      'payment_processor_type_id' => $payment_processor_type_id,
      'domain_id' => CRM_Core_Config::domainID(),
    ));

    $manager->disable(array('test.extension.manager.paymenttest'));
    $this->assertEquals(1, test_extension_manager_paymenttest::$counts['disable']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest" AND is_active = 0');

    // first attempt to uninstall -- fail
    try {
      $manager->uninstall(array('test.extension.manager.paymenttest'));
      $this->fail('Failed to catch expected exception');
    } catch (CRM_Extension_Exception_DependencyException $e) {
    }
    $this->assertEquals(0, test_extension_manager_paymenttest::$counts['uninstall']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest"');

    $ppDAO->delete();

    // second attempt to uninstall -- ok
    $manager->uninstall(array('test.extension.manager.paymenttest'));
    $this->assertEquals(1, test_extension_manager_paymenttest::$counts['uninstall']);
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_payment_processor_type WHERE class_name = "test.extension.manager.paymenttest"');
  }
}
