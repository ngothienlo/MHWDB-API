<?php
	namespace App\Controller;

	use App\Contrib\ApiErrors\CreateError;
	use App\Contrib\ApiErrors\InvalidPayloadError;
	use App\Contrib\ApiErrors\UpdateError;
	use App\Contrib\Data\EntityDataInterface;
	use App\Contrib\EntityType;
	use App\Contrib\Management\ContribManager;
	use App\Response\NoContentResponse;
	use DaybreakStudios\Doze\Errors\ApiErrorInterface;
	use DaybreakStudios\Doze\Errors\NotFoundError;
	use DaybreakStudios\DozeBundle\ResponderService;
	use Symfony\Component\HttpFoundation\JsonResponse;
	use Symfony\Component\HttpFoundation\Request;
	use Symfony\Component\HttpFoundation\Response;
	use Symfony\Component\Routing\Annotation\Route;

	class ContribController {
		/**
		 * @var ResponderService
		 */
		protected $responder;

		/**
		 * @var ContribManager
		 */
		protected $contribManager;

		/**
		 * ContribController constructor.
		 *
		 * @param ResponderService $responder
		 * @param ContribManager   $contribManager
		 */
		public function __construct(ResponderService $responder, ContribManager $contribManager) {
			$this->responder = $responder;
			$this->contribManager = $contribManager;
		}

		/**
		 * @Route(path="/contrib/{type<[a-z-]+>}", methods={"PUT"}, name="contrib.create")
		 *
		 * @param Request $request
		 * @param string  $type
		 *
		 * @return Response
		 */
		public function create(Request $request, string $type): Response {
			if (!EntityType::isValid($type))
				return $this->respond(new NotFoundError());

			$class = EntityType::getDataClass($type);

			if (!$class)
				return $this->respond(new NotFoundError());

			$payload = json_decode($request->getContent());

			if (json_last_error() !== JSON_ERROR_NONE || !$payload || !get_object_vars($payload))
				return $this->respond(new InvalidPayloadError());

			try {
				/** @var EntityDataInterface $data */
				$data = call_user_func([$class, 'fromJson'], $payload);
			} catch (\Exception $e) {
				return $this->respond(new CreateError());
			}

			$output = $data->normalize();

			$id = $this->contribManager->getGroup($type)->create($output, $data->getEntityGroupName(true));

			return $this->respond($output, false, null, [
				'X-Contrib-Data-Id' => $id,
			]);
		}

		/**
		 * @Route(path="/contrib/{type<[a-z-]+>}/{id<[A-Za-z\d-]+>}", methods={"GET"}, name="contrib.read")
		 *
		 * @param string $type
		 * @param string $id
		 *
		 * @return Response
		 */
		public function read(string $type, string $id): Response {
			if (!EntityType::isValid($type))
				return $this->respond(new NotFoundError());

			$data = $this->contribManager->getGroup($type)->get($id);

			if (!$data)
				return $this->respond(new NotFoundError());

			return $this->respond($data);
		}

		/**
		 * @Route(path="/contrib/{type<[a-z-]+>}/{id<[A-Za-z\d-]+>}", methods={"PATCH"}, name="contrib.update")
		 *
		 * @param Request $request
		 * @param string  $type
		 * @param string  $id
		 *
		 * @return Response
		 */
		public function update(Request $request, string $type, string $id): Response {
			if (!EntityType::isValid($type))
				return $this->respond(new NotFoundError());

			$class = EntityType::getDataClass($type);

			if (!$class)
				return $this->respond(new NotFoundError());

			$payload = json_decode($request->getContent());

			if (json_last_error() !== JSON_ERROR_NONE || !$payload || !get_object_vars($payload))
				return $this->respond(new InvalidPayloadError());

			$group = $this->contribManager->getGroup($type);
			$data = $group->get((int)$id);

			if (!$data)
				return $this->respond(new NotFoundError());

			try {
				/** @var EntityDataInterface $data */
				$data = call_user_func([$class, 'fromJson'], $data);
				$data->update($payload);
			} catch (\Exception $e) {
				return $this->respond(new UpdateError());
			}

			$group->put((int)$id, $output = $data->normalize(), $data->getEntityGroupName(true));

			return $this->respond($output);
		}

		/**
		 * @Route(path="/contrib/{type<[a-z-]+>}/{id<[A-Za-z\d-]+>}", methods={"DELETE"}, name="contrib.delete")
		 *
		 * @param string $type
		 * @param string $id
		 *
		 * @return Response
		 */
		public function delete(string $type, string $id): Response {
			if (!EntityType::isValid($type))
				return $this->respond(new NotFoundError());

			$found = $this->contribManager->getGroup($type)->delete($id);

			if (!$found)
				return $this->respond(new NotFoundError());

			return new NoContentResponse();
		}

		/**
		 * @param ApiErrorInterface|string|array|object $data
		 * @param bool                                  $json
		 * @param null                                  $status
		 * @param array                                 $headers
		 *
		 * @return JsonResponse|Response
		 */
		protected function respond($data, bool $json = false, $status = null, array $headers = []) {
			if ($data instanceof ApiErrorInterface)
				return $this->responder->createErrorResponse($data, $status, $headers);

			return new JsonResponse($data, $status ?? Response::HTTP_OK, $headers + [
					'Cache-Control' => 'public, max-age=14400',
					'Content-Type' => 'application/json',
				], $json);
		}
	}