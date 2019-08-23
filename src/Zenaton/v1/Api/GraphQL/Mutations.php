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
                    cron
                    id
                    name
                    target {
                        ... on WorkflowTarget {
                            canonicalName
                            codePathVersion
                            initialLibraryVersion
                            name
                            programmingLanguage
                            properties
                            type
                        }
                    }
                    insertedAt
                    updatedAt
                }
            }
        }
MUTATION;

    const CREATE_TASK_SCHEDULE = <<<'MUTATION'
        mutation createTaskSchedule($input: CreateTaskScheduleInput!) {
            createTaskSchedule(input: $input) {
                schedule {
                    cron
                    id
                    name
                    target {
                        ... on TaskTarget {
                            codePathVersion
                            initialLibraryVersion
                            name
                            programmingLanguage
                            properties
                            type
                        }
                    }
                    insertedAt
                    updatedAt
                }
            }
        }
MUTATION;

    private function __construct()
    {
    }
}
