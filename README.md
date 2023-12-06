# lyyti-participant-counts-wp-plugin

WordPress plugin which exposes number of participants from [Lyyti](https://www.lyyti.com/) events in shortcodes using the [Lyyti API](https://lyyti.readme.io/docs).

## Installation

1. Copy the `lyyti-participant-counts` directory under the `/wp-content/plugins/` directory in your WordPress installation.
2. Enable the `Lyyti Participant Counts` plugin from the `Plugins` menu in WordPress.
3. Add your Lyyti API credentials under `Settings -> Lyyti Options` in WordPress.
4. Optionally configure a default Lyyti event id (eid) and a custom cache lifetime in the plugin options.

## Using the plugin

This plugin provides one single shortcode, `[lyyti-participant-count]`, which can be used to show the number of participants from a Lyyti event.

In the plugin options, you can configure a default Lyyti event id (eid) and default participant statuses.

If you only want to display participant counts for one single Lyyti event at a time, you can set the event id (eid) in the plugin settings and update it there as required.

If you only want to count the number of registered (`reactedyes`) and attending (`show`) participants, you can use the default participant status (`reactedyes,show`) as-is. You can also change it if your use case requires you to count participants with any of the statuses declined (`reactedno`), not reacted (`notreacted`) or no show (`noshow`).

### Overriding the defaults

In the shortcode, you can optionally override the `eid` and `status` parameters, instead of relying on the defaults configured for the plugin. This is useful if you want to show numbers for multiple events, and/or for multiple different participant statuses within an event.

You can override both of the `eid` and `status` attributes at the same time, as your use case requires.

#### Overriding the event id

The Lyyti event id can be overridden within the shortcode using the `eid` attribute, e.g. as follows:

> There are `[lyyti-participant-count eid=1234567]` participants in the event.

#### Overriding the status

The participant status can be overridden within the shortcode using the `status` attribute, e.g. as follows:

> The event is live and there are already `[lyyti-participant-count status=show]` attendees on site! Join us!

### Caching

In order to not spam the Lyyti API on every WordPress page load, the plugin will cache the API responses for a pre-defined period of time. This can be configured under `Cache lifetime` in the plugin settings, and defaults to 10 minutes (600 seconds).

## Troubleshooting and error handling

Sometimes everything does not work out as planned. If the plugin is misconfigured or the Lyyti API does not respond as expected, you can face a number of different error cases. Currently these are being handled by outputting the error code in place of the participant count. This is not optimal and could be significantly improved.

The possible error codes are:

- `ERROR_LYYTI_EID_UNDEFINED`
- `ERROR_LYYTI_STATUS_UNDEFINED`
- `ERROR_LYYTI_API_CREDENTIALS_MISSING`
- `ERROR_LYYTI_UNEXPECTED_API_RESPONSE`

These should all be quite self-explanatory.