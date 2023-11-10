<?php

namespace App;

use Closure;
use ReflectionProperty;

class Live
{
	public static self $instance;
	public $view = [
		'blocks' => [],
	];
	public array $defaultRoute = ['class' => null, 'args' => []];
	public array $routes = [
		'GET' => [],
		'POST' => [],
	];
	public static function make(string $name = 'app'): self
	{
		return self::$instance ??= new self();
	}

	public function defaultRoute(string $class): self
	{
		$this->defaultRoute['class'] = $class;
		return $this;
	}

	public static function abort(string $code)
	{
		http_response_code($code);
		die;
	}

	/**
	 * register page
	 * @param string $path 
	 * @param string $class 
	 * @return Live 
	 */
	public function page(string $path, string $class): self
	{
		$this->routes['GET'][$path] = $class;
		return $this;
	}

	public function render(string $template, array $data = [])
	{
		$live = fn ($class) => $this->initialComponentRender($class);
		extract($data, EXTR_SKIP);
		$code = $this->includeViews($template);
		$code = $this->compileView($code);
		ob_start();
		eval('?>' . $code);
		return ob_get_clean();
	}

	public function compileView($code)
	{
		$code = self::parseBlock($code);
		$code = self::parseYield($code);
		$code = self::parseEscapedEchos($code);
		$code = self::parseEchos($code);
		$code = self::parsePHP($code);
		return $code;
	}

	/** listen requests @return void  */
	public function start()
	{
		$this->routes['POST']['/live'] = fn () => $this->live();
		$uri = $_SERVER['REQUEST_URI'];
		$method = $_SERVER['REQUEST_METHOD'];
		$routes = $this->routes[$method];
		$class = $routes[$uri] ??= $this->defaultRoute['class'];
		if ($class instanceof Closure) {
			echo $class();
			die;
		}
		$controller = new $class();
		echo $controller->render();
	}


	protected function setComponentProperties($component, $data)
	{
		foreach ($data as $property => $value) {
			$component->$property = $value;
		}
	}

	protected function getComponentProperties($component): array
	{
		$properties = [];
		$reflectedProperties = (new \ReflectionClass($component))->getProperties(ReflectionProperty::IS_PUBLIC);
		foreach ($reflectedProperties as $property) {
			$properties[$property->getName()] = $property->getValue($component);
		}
		return $properties;
	}

	protected function callComponentMethod($page, $method)
	{
		$page->$method();
	}

	protected function updateComponentProperty($page, $property, $value): void
	{
		$page->{$property} = $value;
		$updatedHook = $property . 'Updated';
		if (method_exists($page, $updatedHook)) {
			$page->{$updatedHook}();
		}
	}

	protected function hydrateComponentProperties($data, $meta)
	{
		$properties = [];
		foreach ($data as $key => $value) {
			if (isset($meta[$key])) {
				if ($meta['key'] == 'collection') {
					// ....
				}
			}
			$properties[$key] = $value;
		}
		return $properties;
	}

	protected function dehydrateComponentProperties(array $properties)
	{
		$data = $meta = [];
		foreach ($properties as $key => $value) {
			// if ($value instanceof Collection) {
			// 	$value = $value->toArray();
			// 	$meta[$key] = 'collection';
			// }
			$data[$key] = $value;
		}
		return [$data, $meta];
	}

	protected function toSnapshot($page): array
	{
		$properties = $this->getComponentProperties($page);
		[$data, $meta] = $this->dehydrateComponentProperties($properties);
		foreach ($data as $key => $value) {
			$page->{$key} = $value;
		}
		$html = $page->render();
		$snapshot = [
			'class' => get_class($page),
			'data' => $data,
			'meta' => $meta,
		];
		$snapshot['checksum'] = $this->generateChecksum($snapshot);
		return [$html, $snapshot];
	}

	protected function fromSnapshot($snapshot)
	{
		$this->verifyChecksum($snapshot);
		$class = $snapshot['class'];
		$data = $snapshot['data'];
		$meta = $snapshot['meta'];
		$component = new $class;
		$properties = $this->hydrateComponentProperties($data, $meta);
		$this->setComponentProperties($component, $properties);
		return $component;
	}

	protected function verifyChecksum($snapshot)
	{
		$checksum = $snapshot['checksum'];
		unset($snapshot['checksum']);
		if ($checksum !== $this->generateChecksum($snapshot)) {
			self::abort(400);
		}
	}

	protected function generateChecksum($snapshot)
	{
		return md5(serialize($snapshot));
	}

	protected function initialComponentRender(string $class)
	{
		$page = new $class;
		if (method_exists($page, 'mount')) {
			$page->mount();
		}
		[$html, $snapshot] = $this->toSnapshot($page);
		$snapshotAttribute = htmlentities(json_encode($snapshot));
		return "<div live:snapshot='$snapshotAttribute'>$html</div>";
	}

	protected function live()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		$snapshot = $data['snapshot'];
		$component = $this->fromSnapshot($snapshot);
		if ($method = $data['callMethod']) {
			$this->callComponentMethod($component, $method);
		}
		if ([$property, $value] = $data['updateProperty'] ?: false) {
			$this->updateComponentProperty($component, $property, $value);
		}
		[$html, $snapshot] = $this->toSnapshot($component);
		return json_encode(compact('html', 'snapshot'));
	}

	protected function parseEchos($code)
	{
		return preg_replace('~\{{\s*(.+?)\s*\}}~is', '<?php echo $1 ?>', $code);
	}


	protected function parseBlock($code)
	{
		preg_match_all('/{% ?block ?(.*?) ?%}(.*?){% ?endblock ?%}/is', $code, $matches, PREG_SET_ORDER);
		foreach ($matches as $value) {
			if (!array_key_exists($value[1], $this->view['blocks'])) $this->view['blocks'][$value[1]] = '';
			if (strpos($value[2], '@parent') === false) {
				$this->view['blocks'][$value[1]] = $value[2];
			} else {
				$this->view['blocks'][$value[1]] = str_replace('@parent', $this->view['blocks'][$value[1]], $value[2]);
			}
			$code = str_replace($value[0], '', $code);
		}
		return $code;
	}

	protected function parseEscapedEchos($code)
	{
		return preg_replace('~\{{{\s*(.+?)\s*\}}}~is', '<?php echo htmlentities($1, ENT_QUOTES, \'UTF-8\') ?>', $code);
	}

	protected function parseYield($code)
	{
		foreach ($this->view['blocks'] as $block => $value) {
			$code = preg_replace('/{% ?yield ?' . $block . ' ?%}/', $value, $code);
		}
		$code = preg_replace('/{% ?yield ?(.*?) ?%}/i', '', $code);
		return $code;
	}

	protected function parsePHP($code)
	{
		return preg_replace('~\{%\s*(.+?)\s*\%}~is', '<?php $1 ?>', $code);
	}

	protected function includeViews($file)
	{
		$code = file_get_contents($file);
		preg_match_all('/{% ?(extends|include) ?\'?(.*?)\'? ?%}/i', $code, $matches, PREG_SET_ORDER);
		foreach ($matches as $value) {
			$code = str_replace($value[0], self::includeViews($value[2]), $code);
		}
		$code = preg_replace('/{% ?(extends|include) ?\'?(.*?)\'? ?%}/i', '', $code);
		return $code;
	}
}
