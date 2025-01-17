[⬅️ Back to Documentation overview](/nova-calendar)

---

# Event visibility

## What events are shown by default?
Events for Nova resources the current user is not authorized to see due to Laravel policies are excluded from the calendar automatically.

All instances of a Nova resource will be shown if no Laravel policy is defined for the underlying Eloquent model or if the static `authorizable` method on the Nova resource class returns `false`, unless you hide specific instances manually by implementing the `excludeResource` method on your CalendarDataProvider; see below.

## Hiding individual events
You can exclude specific instances of Nova resources from the calendar by implementing the `excludeResource` method on your CalendarDataProvider.

For example, if you want to hide events for resources with an Eloquent model that have an `is_finished` property that is `true`, you could write:

```php
use Laravel\Nova\Resource as NovaResource;
```
```php
protected function excludeResource(NovaResource $resource) : bool
{
    return $resource->model()->is_finished;
}
```


## A note with regard to event filters

As of version 2.0 of this package, you can implement [Event filters](/nova-calendar/event-filters.html) that allow the end user to choose which events to show on the calendar dynamically.

Events hidden due to Laravel policies or using the `excludeResource` method as described above are never shown to the user, regardless of any event filters that may be active.

[⬅️ Back to Documentation overview](/nova-calendar)