<?php


namespace Lufeijun1234\Http;

use InvalidArgumentException;
use JsonSerializable;
use Lufeijun1234\Abstracts\Support\Arrayable;
use Lufeijun1234\Abstracts\Support\Jsonable;
use Lufeijun1234\Traits\Macroable;
use Symfony\Component\HttpFoundation\JsonResponse as BaseJsonResponse;

class JsonResponse extends BaseJsonResponse
{
	use ResponseTrait, Macroable {
		Macroable::__call as macroCall;
	}

	public function __construct($data = null, $status = 200, $headers = [], $options = 0)
	{
		$this->encodingOptions = $options;

		parent::__construct($data, $status, $headers);
	}

	public function withCallback($callback = null)
	{
		return $this->setCallback($callback);
	}


	public function getData($assoc = false, $depth = 512)
	{
		return json_decode($this->data, $assoc, $depth);
	}

	public function setData($data = [])
	{
		$this->original = $data;

		if ($data instanceof Jsonable) {
			$this->data = $data->toJson($this->encodingOptions);
		} elseif ($data instanceof JsonSerializable) {
			$this->data = json_encode($data->jsonSerialize(), $this->encodingOptions);
		} elseif ($data instanceof Arrayable) {
			$this->data = json_encode($data->toArray(), $this->encodingOptions);
		} else {
			$this->data = json_encode($data, $this->encodingOptions);
		}

		if (! $this->hasValidJson(json_last_error())) {
			throw new InvalidArgumentException(json_last_error_msg());
		}

		return $this->update();
	}


	protected function hasValidJson($jsonError)
	{
		if ($jsonError === JSON_ERROR_NONE) {
			return true;
		}

		return $this->hasEncodingOption(JSON_PARTIAL_OUTPUT_ON_ERROR) &&
			in_array($jsonError, [
				JSON_ERROR_RECURSION,
				JSON_ERROR_INF_OR_NAN,
				JSON_ERROR_UNSUPPORTED_TYPE,
			]);
	}

	public function hasEncodingOption($option)
	{
		return (bool) ($this->encodingOptions & $option);
	}


	public function setEncodingOptions($options)
	{
		$this->encodingOptions = (int) $options;

		return $this->setData($this->getData());
	}

}
