# Upgrade instructions

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
