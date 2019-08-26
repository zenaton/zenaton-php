# Upgrade instructions

## 0.5.0

* The return type of the `Zenaton\Traits\Zenatonable::schedule()` method is now `void`.

  If you were relying on the return value you cannot use it anymore.

  Before:

  ```php
  $schedule = (new RegisterOrderTask())->schedule('* * * * *');
  // [...] Use of $schedule
  ```

  After:

  ```php
  (new RegisterOrderTask())->schedule('* * * * *');
  ```

## 0.4.0

* A `context` property, and methods `::setContext()` and `::getContext()` have been added to the
  `Zenatonable` trait.

  If you have some tasks or workflows using the same names you will have to rename them.

  Before:

  ```php
  class RegisterOrderTask implements TaskInterface
  {
      use Zenaton\Trait\Zenatonable;

      private $context;

      public function __construct($context)
      {
          $this->context = $context;
      }

      public function handle()
      {
          // [...]
      }
  }
  ```

  After:

  ```php
  class RegisterOrderTask implements TaskInterface
  {
      use Zenaton\Trait\Zenatonable;

      private $orderContext;

      public function __construct($context)
      {
          $this->orderContext = $context;
      }

      public function handle()
      {
          // [...]
      }
  }
  ```

  Please note that the `::setContext()` method is internal and must not be used. It is called by the
  Zenaton Agent to set the runtime context of tasks and workflows.

## 0.3.0

* Using `Zenatonable::dispatch()` on tasks outside of a workflow now executes tasks asynchronously.

  The previous behaviour, which was not documented, was to run the task synchronously.
  This change allows you to run a single task asynchronously without having to create a workflow.

  Before:

  ```php
  class SingleTaskDispatchWorkflow implements WorkflowInterface
  {
      public function handle()
      {
          (new SingleTask())->dispatch();
      }
  }

  (new SingleTaskDispatchWorkflow())->dispatch();
  ```

  After:

  ```php
  (new SingleTask())->dispatch();
  ```
