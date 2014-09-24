<?php
namespace Crud\TestCase\Controller\Crud;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;
use Crud\Controller\Component\CrudComponent;
use Crud\TestSuite\ControllerTestCase;

/**
 * TestCrudEventManager
 *
 * This manager class is used to replace the EventManger instance.
 * As such, it becomes a global listener and is used to keep a log of
 * all events fired during the test
 */
class TestCrudEventManager extends \Cake\Event\EventManager {

	protected $_log = array();

	public function dispatch($event) {
		$this->_log[] = array(
			'name' => $event->name(),
			'subject' => $event->subject()
		);
		parent::dispatch($event);
	}

	public function getLog($params = array()) {
		$params += array('clear' => true, 'format' => 'names');

		$log = $this->_log;

		if ($params['format'] === 'names') {
			$return = array();
			foreach ($log as $entry) {
				$return[] = $entry['name'];
			}
			$log = $return;
		}

		if ($params['clear']) {
			$this->_log = array();
		}

		return $log;
	}

}

class CrudExamplesController extends \Cake\Controller\Controller {

	public $modelClass = 'CrudExamples';

	public static $componentsArray = array(
		'Session',
		'Crud.Crud' => array(
			'actions' => array(
				'Crud.Index',
				'Crud.Add',
				'Crud.Edit',
				'Crud.Delete',
				'Crud.View'
			)
		)
	);

	public $paginate = array(
		'limit' => 1000
	);

/**
 * Make it possible to dynamically define the components array during tests
 *
 * @param CakeRequest $request
 * @param CakeResponse $response
 * @return void
 */
	public function __construct($request = null, $response = null) {
		$this->components = self::$componentsArray;

		return parent::__construct($request, $response);
	}

/**
 * add
 *
 * Used in the testAddActionTranslatedBaseline test
 *
 * @return void
 */
	public function add() {
		return $this->Crud->execute();
	}

/**
 * Test that it should render 'search.ctp'
 *
 * @return void
 */
	public function search() {
		return $this->Crud->execute('index');
	}

/**
 * Test that it should render 'index'
 *
 * @return void
 */
	public function index() {
		return $this->Crud->execute('index');
	}

}

/**
 * TestCrudComponent
 *
 * Expose protected methods so we can test them in isolation
 */
class TestCrudComponent extends \Crud\Controller\Component\CrudComponent {

/**
 * test visibility wrapper - access protected _modelName property
 */
	public function getModelName() {
		return $this->_modelName;
	}

/**
 * test visibility wrapper - allow on the fly change of action name
 */
	public function setAction($name) {
		$this->_action = $name;
	}

}

class TestListener extends \Crud\Listener\BaseListener {

	public $callCount = 0;

	public function setup() {
		$this->callCount += 1;
	}

}

/**
 * CrudComponentTestCase
 */
class CrudComponentTest extends ControllerTestCase {

/**
 * Fixtures
 *
 * Use the core posts fixture to have something to work on.
 * What fixture is used is almost irrelevant, was chosen as it is simple
 */
	public $fixtures = array(
		'core.posts'
	);

/**
 * setUp
 *
 * Setup the classes the crud component needs to be testable
 */
	public function setUp() {
		parent::setUp();

		EventManager::instance(new TestCrudEventManager());

		$this->model = TableRegistry::get('CrudExamples');

		$this->request = $this->getMock('Cake\Network\Request', array('is', 'method'));
		$this->request->expects($this->any())->method('is')->will($this->returnValue(true));

		$response = new Response();
		$this->controller = $this->getMock(
			'Crud\TestCase\Controller\Crud\CrudExamplesController',
			array('header', 'redirect', 'render', '_stop'),
			array($this->request, $response, 'CrudExamples', EventManager::instance())
		);
		$this->controller->methods = array();

		$this->Registry = $this->controller->components();

		$config = array(
			'actions' => array(
				'Crud.Index',
				'Crud.Add',
				'Crud.Edit',
				'Crud.View',
				'Crud.Delete'
			)
		);

		$this->Crud = new TestCrudComponent($this->Registry, $config);
		$this->Crud->initialize(new Event('Controller.initialize'));
		$this->controller->Crud = $this->Crud;
	}

/**
 * tearDown method
 */
	public function tearDown() {
		unset(
			$this->model,
			$this->request,
			$this->controller,
			$this->Crud,
			$this->Registry
		);

		parent::tearDown();
	}

/**
 * Test config normalization
 *
 * @return void
 */
	public function testConfigNormalization() {
		$config = array(
			'actions' => array(
				'Crud.Index',
				'add' => 'Crud.Add',
				'view' => ['className' => 'Crud.View', 'viewVar' => 'beers'],
			),
			'listeners' => array(
				'Crud.Related'
			)
		);
		$Crud = $this->getMock(
			'Crud\Controller\Component\CrudComponent',
			array('_loadListeners', 'trigger'),
			array($this->Registry, $config)
		);
		$Crud
			->expects($this->once())
			->method('_loadListeners');
		$Crud
			->expects($this->once())
			->method('trigger');
		$Crud->initialize(new Event('Controller.initialize'));

		$expected = array(
			'index' => array('className' => 'Crud.Index'),
			'add' => array('className' => 'Crud.Add'),
			'view' => array('className' => 'Crud.View', 'viewVar' => 'beers'),
		);
		$this->assertEquals($expected, $Crud->config('actions'));

		$expected = array(
			'related' => array('className' => 'Crud.Related'),
		);
		$this->assertEquals($expected, $Crud->config('listeners'));
	}

/**
 * Test deprecated `executeAction` calls `execute` correctly
 *
 */
	public function testExecuteActionToExecute() {
		$config = array('actions' => array('Crud.Index'));

		$Crud = $this->getMock(
			'Crud\Controller\Component\CrudComponent',
			array('execute'),
			array($this->Registry, $config)
		);
		$Crud
			->expects($this->once())
			->method('execute')
			->with('index', array('foo' => 'bar'));

		$Crud->execute('index', array('foo' => 'bar'));
	}

/**
 * testEnable
 *
 */
	public function testEnable() {
		$this->Crud->mapAction('puppies', 'Crud.View', false);
		$this->Crud->enable('puppies');

		$result = $this->Crud->isActionMapped('puppies');
		$this->assertTrue($result);
	}

/**
 * testDisableAction
 *
 */
	public function testDisableAction() {
		$this->Crud->disable('view');

		$result = $this->Crud->isActionMapped('view');
		$this->assertFalse($result);
	}

/**
 * testMapAction
 *
 */
	public function testMapAction() {
		$this->Crud->mapAction('puppies', 'Crud.View');

		$result = $this->Crud->isActionMapped('puppies');
		$this->assertTrue($result);

		$this->Crud->mapAction('kittens', array(
			'className' => 'Crud.Index',
			'relatedModels' => false
		));

		$result = $this->Crud->isActionMapped('kittens');
		$this->assertTrue($result);

		$expected = array(
			'className' => 'Crud.Index',
			'relatedModels' => false
		);
		$this->assertEquals($expected, $this->Crud->config('actions.kittens'));
	}

/**
 * testView
 *
 */
	public function testView() {
		$this->request
			->expects($this->once())
			->method('method')
			->will($this->returnValue('GET'));

		$this->controller
			->expects($this->once())
			->method('render');

		$this->Crud->view('view', 'cupcakes');
		$this->Crud->execute('view', array(1));
	}

/**
 * testIsActionMappedYes
 *
 */
	public function testIsActionMappedYes() {
		$result = $this->Crud->isActionMapped('index');
		$this->assertTrue($result);

		$this->controller->request->action = 'edit';
		$this->Crud->initialize(new Event('Controller.initialize'));
		$result = $this->Crud->isActionMapped();
		$this->assertTrue($result);
	}

/**
 * testIsActionMappedNo
 *
 */
	public function testIsActionMappedNo() {
		$result = $this->Crud->isActionMapped('puppies');
		$this->assertFalse($result);

		$this->controller->action = 'rainbows';
		$this->Crud->initialize(new Event('Controller.initialize'));
		$result = $this->Crud->isActionMapped();
		$this->assertFalse($result);
	}

/**
 * Tests on method registers an event
 *
 */
	public function testOn() {
		$this->Crud->on('event', 'fakeCallback');

		$return = $this->controller->eventManager()->listeners('Crud.event');

		$expected = array(
			array(
				'callable' => 'fakeCallback'
			)
		);
		$this->assertSame($expected, $return);
	}

/**
 * tests on method registers an event with extra params
 *
 */
	public function testOnWithPriPriority() {
		$this->Crud->on('event', 'fakeCallback');
		$this->Crud->on('event', 'fakeHighPriority', array('priority' => 1));
		$this->Crud->on('event', 'fakeLowPriority', array('priority' => 99999));

		$return = $this->controller->eventManager()->listeners('Crud.event');

		$expected = array(
			array(
				'callable' => 'fakeHighPriority'
			),
			array(
				'callable' => 'fakeCallback'
			),
			array(
				'callable' => 'fakeLowPriority'
			)
		);
		$this->assertSame($expected, $return);
	}

/**
 * Test if crud complains about unmapped actions
 *
 * @expectedException \Exception
 * @return void
 */
	public function testCrudWillComplainAboutUnmappedAction() {
		$this->Crud->execute('show_all');
	}

/**
 * Test if view with array yields the expected result
 *
 * @return void
 */
	public function testViewWithArrayNewAction() {
		$this->request
			->expects($this->once())
			->method('method')
			->will($this->returnValue('GET'));

		$this->request
			->expects($this->once())
			->method('method')
			->will($this->returnValue('GET'));

		$this->controller
			->expects($this->once())
			->method('render')
			->with('index');

		$this->Crud->mapAction('show_all', ['className' => 'Crud.index']);
		$this->Crud->view(array('show_all' => 'index', 'index' => 'overview'));

		$this->Crud->execute('showAll');
	}

/**
 * Test if view with array yields the expected result
 *
 * @return void
 */
	public function testViewWithArrayIndexAction() {
		$this->request
			->expects($this->once())
			->method('method')
			->will($this->returnValue('GET'));

		$this->controller
			->expects($this->once())
			->method('render')
			->with('overview');

		$this->Crud->mapAction('show_all', ['className' => 'Crud.index']);
		$this->Crud->view(array('show_all' => 'index', 'index' => 'overview'));

		$this->Crud->execute('index');
	}

/**
 * Test that having no mapped model for an action,
 * just use the modelClass from the controller
 *
 * @return void
 */
	public function testSetModelPropertiesDefault() {
		$this->markTestSkipped(
			'Tests still not updated.'
		);

		$this->Crud->setAction('index');
		$this->assertSame('CrudExamples', $this->Crud->getModelName());
	}

/**
 * testMappingNonExistentAction
 *
 * @expectedException \Exception
 * @expectedExceptionMessage Could not find action class: Sample.Index
 * @return void
 */
	public function testMappingNonExistentAction() {
		$this->Crud->mapAction('test', 'Sample.Index');
	}

/**
 * Test that having a 'search' action in the controller
 * and calling ->execute('index') will still
 * render the 'search' view
 *
 * @return void
 */
	public function testViewCanBeChangedInControllerAction() {
		$this->request
			->expects($this->once())
			->method('method')
			->will($this->returnValue('GET'));

		$this->request->action = 'search';

		$this->controller
			->expects($this->once())
			->method('render')
			->with('search');

		$this->controller->search();
	}

/**
 * Test the default configuration for CrudComponent
 *
 * @return void
 */
	public function testDefaultConfig() {
		$Crud = new CrudComponent($this->Registry);

		$result = $Crud->config();
		$expected = array(
			'actions' => array(),
			'eventPrefix' => 'Crud',
			'listeners' => array(),
			'messages' => array(
				'domain' => 'crud',
				'invalidId' => array(
					'code' => 400,
					'class' => 'Cake\Network\Exception\BadRequestException',
					'text' => 'Invalid id'
				),
				'recordNotFound' => array(
					'code' => 404,
					'class' => 'Cake\Network\Exception\NotFoundException',
					'text' => 'Not found'
				),
				'badRequestMethod' => array(
					'code' => 405,
					'class' => 'Cake\Network\Exception\MethodNotAllowedException',
					'text' => 'Method not allowed. This action permits only {methods}'
				)
			),
			'eventLogging' => false
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test that providing configuration for a new
 * listener in the Crud setting should preserve
 * the defaults and add the new listener to the array
 *
 * @return void
 */
	public function testConstructMerging() {
		$config = array(
			'listeners' => array(
				'api' => 'Crud.Api'
			)
		);

		$Crud = new CrudComponent($this->Registry, $config);
		$result = $Crud->config();
		$expected = array(
			'actions' => array(),
			'eventPrefix' => 'Crud',
			'listeners' => array(
				'api' => array('className' => 'Crud.Api')
			),
			'messages' => array(
				'domain' => 'crud',
				'invalidId' => array(
					'code' => 400,
					'class' => 'Cake\Network\Exception\BadRequestException',
					'text' => 'Invalid id'
				),
				'recordNotFound' => array(
					'code' => 404,
					'class' => 'Cake\Network\Exception\NotFoundException',
					'text' => 'Not found'
				),
				'badRequestMethod' => array(
					'code' => 405,
					'class' => 'Cake\Network\Exception\MethodNotAllowedException',
					'text' => 'Method not allowed. This action permits only {methods}'
				)
			),
			'eventLogging' => false
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test that providing configuration for a new
 * listener in the Crud setting should preserve
 * the defaults and add the new listener to the array
 *
 * @return void
 */
	public function testConstructMerging2() {
		$config = array(
			'listeners' => array(
			)
		);

		$Crud = new CrudComponent($this->Registry, $config);
		$result = $Crud->config();
		$expected = array(
			'actions' => array(),
			'eventPrefix' => 'Crud',
			'listeners' => array(),
			'messages' => array(
				'domain' => 'crud',
				'invalidId' => array(
					'code' => 400,
					'class' => 'Cake\Network\Exception\BadRequestException',
					'text' => 'Invalid id'
				),
				'recordNotFound' => array(
					'code' => 404,
					'class' => 'Cake\Network\Exception\NotFoundException',
					'text' => 'Not found'
				),
				'badRequestMethod' => array(
					'code' => 405,
					'class' => 'Cake\Network\Exception\MethodNotAllowedException',
					'text' => 'Method not allowed. This action permits only {methods}'
				)
			),
			'eventLogging' => false
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test that addListener works - without listener
 * default config
 *
 * @return void
 */
	public function testAddListenerWithoutDefaults() {
		$listeners = $this->Crud->config('listeners');
		$expected = array();

		$this->assertEquals($expected, $listeners);

		$this->Crud->addListener('api', 'Crud.Api');

		$listeners = $this->Crud->config('listeners');
		$expected = array(
			'api' => array('className' => 'Crud.Api')
		);
		$this->assertEquals($expected, $listeners);

		$this->assertEquals(
			array('className' => 'Crud.Api'),
			$this->Crud->defaults('listeners', 'api')
		);
	}

/**
 * Test that addListener works - with listener
 * default config
 *
 * @return void
 */
	public function testAddListenerWithDefaults() {
		$this->Crud->addListener('api', 'Crud.Api', array('test' => 1));

		$listeners = $this->Crud->config('listeners');
		$expected = array(
			'api' => array('className' => 'Crud.Api', 'test' => 1)
		);
		$this->assertEquals($expected, $listeners);

		$this->assertEquals(
			array('className' => 'Crud.Api', 'test' => 1),
			$this->Crud->defaults('listeners', 'api')
		);
	}

/**
 * Test that removeListener works
 *
 * @return void
 */
	public function testRemoveListener() {
		$this->Crud->addListener('api', 'Crud.Api');
		$listeners = $this->Crud->config('listeners');
		$expected = array(
			'api' => array('className' => 'Crud.Api')
		);
		$this->assertEquals($expected, $listeners);

		$this->Crud->removeListener('api');
		$listeners = $this->Crud->config('listeners');
		$this->assertEquals(array(), $listeners);

		// Should now throw an exception
		$this->setExpectedException('Exception', 'Listener "api" is not configured');
		$this->Crud->listener('api');
	}

/**
 * Test removing a listener that doesn't exist
 * should return false
 *
 * @return void
 */
	public function testRemoveListenerNoExist() {
		$this->assertFalse($this->Crud->removeListener('invalid_name'));
	}

/**
 * Test changing view var for one action works
 *
 * @return void
 */
	public function testViewVarSingleAction() {
		$this->Crud->viewVar('index', 'my_var');

		$expected = 'my_var';
		$result = $this->Crud->action('index')->viewVar();
		$this->assertEquals($expected, $result);
	}

/**
 * Test changing view var for multiple actions works
 *
 * @return void
 */
	public function testViewVarMultipleActions() {
		$this->Crud->viewVar(array('index' => 'my_var', 'view' => 'view_var'));

		$expected = 'my_var';
		$result = $this->Crud->action('index')->viewVar();
		$this->assertEquals($expected, $result);

		$expected = 'view_var';
		$result = $this->Crud->action('view')->viewVar();
		$this->assertEquals($expected, $result);
	}

/**
 * Test changing view var for multiple actions works
 *
 * @return void
 */
	public function testFindMethodMultipleActions() {
		$this->markTestSkipped(
			'Tests still not updated.'
		);

		$this->Crud->findMethod(array('index' => 'my_all', 'view' => 'my_view'));

		$expected = 'my_all';
		$result = $this->Crud->action('index')->findMethod();
		$this->assertEquals($expected, $result);

		$expected = 'my_view';
		$result = $this->Crud->action('view')->findMethod();
		$this->assertEquals($expected, $result);
	}

/**
 * Test setting defaults for one action works
 *
 * @return void
 */
	public function testDefaultsOnAction() {
		$this->Crud->defaults('actions', 'index', array('unit_test' => true));
		$config = $this->Crud->defaults('actions', 'index');

		$this->assertTrue($config['unit_test']);
	}

/**
 * Test setting defaults for multiple actions work
 *
 * @return void
 */
	public function testDefaultsMultipleActions() {
		$this->Crud->defaults('actions', array('index', 'view'), array('unit_test' => true));

		$config = $this->Crud->defaults('actions', 'index');
		$this->assertTrue($config['unit_test']);

		$config = $this->Crud->defaults('actions', 'view');
		$this->assertTrue($config['unit_test']);
	}

/**
 * Test setting defaults for one listener works
 *
 * @return void
 */
	public function testDefaultsOneListener() {
		$this->Crud->defaults('listeners', 'translations', array('unit_test' => true));
		$config = $this->Crud->defaults('listeners', 'translations');

		$this->assertTrue($config['unit_test']);
	}

/**
 * Test setting defaults for multiple actions work
 *
 * @return void
 */
	public function testDefaultsMultipleListeners() {
		$this->Crud->defaults(
			'listeners',
			array('translations', 'relatedModels'),
			array('unit_test' => true)
		);

		$config = $this->Crud->defaults('listeners', 'translations');
		$this->assertTrue($config['unit_test']);

		$config = $this->Crud->defaults('listeners', 'relatedModels');
		$this->assertTrue($config['unit_test']);
	}

/**
 * Test setting defaults for one listener works
 *
 * This proves that not setting 'className' doesn't break
 *
 * @return void
 */
	public function testDefaultsListenerNotAlreadyLoaded() {
		$this->Crud->defaults('listeners', 'api', array('unit_test' => true));
		$config = $this->Crud->defaults('listeners', 'api');
		$this->assertTrue($config['unit_test']);
	}

/**
 * Test adding a listener only by class name
 *
 * @return void
 */
	public function testAddListenerOnlyClassName() {
		$this->Crud->addListener('Crud.api');
		$config = $this->Crud->config('listeners');
		$this->assertEquals(array('className' => 'Crud.Api'), $config['api']);
	}

/**
 * Test adding a listener by name and class name
 *
 * @return void
 */
	public function testAddListenerByNameAndClassName() {
		$this->Crud->addListener('foo', 'Crud.Api');
		$config = $this->Crud->config('listeners');
		$this->assertEquals(array('className' => 'Crud.Api'), $config['foo']);
	}

/**
 * Test the Crud sets model and modelClass to NULL
 * if there is no model defined in the controller
 *
 * @return void
 */
	public function testControllerWithEmptyUses() {
		$controller = new Controller(new Request());
		$this->Crud = new CrudComponent($this->Registry, array('actions' => array('index')));
		$this->Crud->initialize(new Event('Controller.initialize'));
		$this->controller->Crud = $this->Crud;
		$this->Crud->config('actions.index', ['className' => 'Crud.Index']);
		$this->Crud->action('index');
		$subject = $this->Crud->trigger('sample');

		$this->assertNull($subject->model);
		$this->assertNull($subject->modelClass);
	}

/**
 * Test that it's possible to change just one sub key
 * by providing all the parents, without loosing any
 * default settings
 *
 * @return void
 */
	public function testConfigMergeWorks() {
		$this->Crud->config(array('messages' => array('invalidId' => array('code' => 500))));

		$expected = array(
			'code' => 500,
			'class' => 'Cake\Network\Exception\BadRequestException',
			'text' => 'Invalid id'
		);
		$result = $this->Crud->config('messages.invalidId');
		$this->assertEquals($expected, $result);
	}

/**
 * Using $key and value, and specifying no merge should overwrite the value keys
 *
 * @return void
 */
	public function testConfigOverwrite() {
		$this->Crud->config('messages.invalidId', array('code' => 500), false);

		$expected = array(
			'domain' => 'crud',
			'invalidId' => array(
				'code' => 500
			),
			'recordNotFound' => array(
				'code' => 404,
				'class' => 'Cake\Network\Exception\NotFoundException',
				'text' => 'Not found'
			),
			'badRequestMethod' => array(
				'code' => 405,
				'class' => 'Cake\Network\Exception\MethodNotAllowedException',
				'text' => 'Method not allowed. This action permits only {methods}'
			)
		);
		$result = $this->Crud->config('messages');
		$this->assertEquals($expected, $result);
	}

/**
 * Tests that is possible to set the model class to use for the action
 *
 * @return void
 */
	public function testUseModel() {
		$this->markTestSkipped(
			'Tests still not updated.'
		);

		$controller = new Controller(new Request());
		$this->Crud = new CrudComponent($this->Registry, array('actions' => array('index')));
		$this->Crud->initialize(new Event('Controller.initialize'));
		$this->controller->Crud = $this->Crud;
		$class = $this->getMockClass('Model');
		$this->Crud->useModel($class);
		$this->Crud->action('index');
		$subject = $this->Crud->trigger('sample');

		$this->assertInstanceOf($class, $subject->model);
		$this->assertEquals($class, $subject->modelClass);
	}

/**
 * testLoadListener
 *
 * @return void
 */
	public function testLoadListener() {
		$this->Crud->config('listeners.HasSetup', array(
			'className' => 'Crud\TestCase\Controller\Crud\TestListener'
		));

		$this->setReflectionClassInstance($this->Crud);
		$listener = $this->callProtectedMethod('_loadListener', array('HasSetup'), $this->Crud);
		$this->assertSame(1, $listener->callCount, 'Setup should be called');
	}

}
