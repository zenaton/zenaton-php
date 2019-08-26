<?php

namespace Zenaton\Api\GraphQL;

/**
 * Contains constants of various GraphQL mutations.
 */
final class Mutations
{
    const CREATE_WORKFLOW_SCHEDULE = <<<'MUTATION'
        mutation createWorkflowSchedule($input: CreateWorkflowScheduleInput!) {
            createWorkflowSchedule(input: $input) {
                schedule {
                    id
                }
            }
        }
MUTATION;

    const CREATE_TASK_SCHEDULE = <<<'MUTATION'
        mutation createTaskSchedule($input: CreateTaskScheduleInput!) {
            createTaskSchedule(input: $input) {
                schedule {
                    id
                }
            }
        }
MUTATION;

    private function __construct()
    {
    }
}
