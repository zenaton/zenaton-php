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

    private function __construct()
    {
    }

    /**
     * @internal should not be called by user code
     *
     * @return self
     */
    public static function fromArray(array $values = [])
    {
        $schedule = new static();
        $schedule->id = (string) $values['id'];
        $schedule->cron = (string) $values['cron'];
        $schedule->name = isset($values['name']) ? (string) $values['name'] : null;
        $schedule->target = static::createTarget($values['target']);
        $schedule->insertedAt = \DateTimeImmutable::createFromFormat(static::API_DATE_FORMAT, $values['insertedAt']);
        $schedule->updatedAt = \DateTimeImmutable::createFromFormat(static::API_DATE_FORMAT, $values['updatedAt']);

        return $schedule;
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
                return WorkflowTarget::fromArray($targetData);
            case 'TASK':
                return TaskTarget::fromArray($targetData);
            default:
                throw new \UnexpectedValueException(sprintf('Unable to create a target of type %s.', $targetData['type']));
        }
    }
}
