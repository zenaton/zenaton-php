<?php

namespace Zenaton\Api\GraphQL;

/**
 * Contains constants of various GraphQL mutations.
 */
final class Mutations
{
    const CREATE_WORKFLOW_SCHEDULE = <<<'CREATE_WORKFLOW_SCHEDULE'
        mutation createWorkflowSchedule($input: CreateWorkflowScheduleInput!) {
            createWorkflowSchedule(input: $input) {
                schedule {
                    id
                }
            }
        }
CREATE_WORKFLOW_SCHEDULE;

    const CREATE_TASK_SCHEDULE = <<<'CREATE_TASK_SCHEDULE'
        mutation createTaskSchedule($input: CreateTaskScheduleInput!) {
            createTaskSchedule(input: $input) {
                schedule {
                    id
                }
            }
        }
CREATE_TASK_SCHEDULE;

    const DISPATCH_TASK = <<<'DISPATCH_TASK'
        mutation dispatchTask($input: DispatchTaskInput!) {
            dispatchTask(input: $input) {
                task {
                    intentId
                }
            }
        }
DISPATCH_TASK;

    const DISPATCH_WORKFLOW = <<<'DISPATCH_WORKFLOW'
        mutation dispatchWorkflow($input: DispatchWorkflowInput!) {
            dispatchWorkflow(input: $input) {
                workflow {
                    canonicalName
                    id
                    name
                    properties
                }
            }
        }
DISPATCH_WORKFLOW;

    const KILL_WORKFLOW = <<<'KILL_WORKFLOW'
        mutation killWorkflow($input: KillWorkflowInput!) {
            killWorkflow(input: $input) {
                id
                intent_id
            }
        }
KILL_WORKFLOW;

    const PAUSE_WORKFLOW = <<<'PAUSE_WORKFLOW'
        mutation pauseWorkflow($input: PauseWorkflowInput!) {
            pauseWorkflow(input: $input) {
                id
                intent_id
            }
        }
PAUSE_WORKFLOW;

    const RESUME_WORKFLOW = <<<'RESUME_WORKFLOW'
        mutation resumeWorkflow($input: ResumeWorkflowInput!) {
            resumeWorkflow(input: $input) {
                id
                intent_id
            }
        }
RESUME_WORKFLOW;

    const SEND_EVENT = <<<'SEND_EVENT'
        mutation sendEventToWorkflowByNameAndCustomId($input: SendEventToWorkflowByNameAndCustomIdInput!) {
            sendEventToWorkflowByNameAndCustomId(input: $input) {
                event {
                    intentId
                    name
                    input
                }
            }
        }
SEND_EVENT;

    private function __construct()
    {
    }
}
