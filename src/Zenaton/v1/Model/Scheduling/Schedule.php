<?php

namespace Zenaton\Model\Scheduling;

final class Schedule
{
    const API_DATE_FORMAT = 'Y-m-d\TH:i:s.u\Z';

    /** @var string */
    private $id;
    /** @var string */
    private $cron;
    /** @var null|string */
    private $name;
    /** @var TaskTarget|WorkflowTarget */
    private $target;
    /** @var \DateTimeInterface */
    private $insertedAt;
    /** @var \DateTimeInterface */
    private $updatedAt;

    public function __construct(array $values)
    {
        $this->id = (string) $values['id'];
        $this->cron = (string) $values['cron'];
        $this->name = isset($values['name']) ? (string) $values['name'] : null;
        $this->target = static::createTarget($values['target']);
        $this->insertedAt = \DateTimeImmutable::createFromFormat(static::API_DATE_FORMAT, $values['insertedAt'], new \DateTimeZone('UTC'));
        $this->updatedAt = \DateTimeImmutable::createFromFormat(static::API_DATE_FORMAT, $values['updatedAt'], new \DateTimeZone('UTC'));
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCron()
    {
        return $this->cron;
    }

    /**
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return TaskTarget|WorkflowTarget
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getInsertedAt()
    {
        return $this->insertedAt;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Creates a new instance of the schedule's target.
     *
     * @return TaskTarget|WorkflowTarget
     */
    private static function createTarget(array $targetData)
    {
        switch ($targetData['type']) {
            case 'WORKFLOW':
                return new WorkflowTarget($targetData);
            case 'TASK':
                return new TaskTarget($targetData);
            default:
                throw new \UnexpectedValueException(sprintf('Unable to create a target of type %s.', $targetData['type']));
        }
    }
}
