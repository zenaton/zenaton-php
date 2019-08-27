<?php

namespace Zenaton\Api\GraphQL;

/**
 * Contains constants of various GraphQL queries.
 */
final class Queries
{
    const WORKFLOW = <<<'WORKFLOW'
        query workflow($workflowName: String, $customId: ID, $environmentName: String, $programmingLanguage: String) {
            workflow(environmentName: $environmentName, programmingLanguage: $programmingLanguage, customId: $customId, name: $workflowName) {
                name
                properties
            }
        }
WORKFLOW;

    private function __construct()
    {
    }
}
