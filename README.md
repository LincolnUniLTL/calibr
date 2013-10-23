Calibr
======
A simple opening hours calendar loading and rendering system. We use it for our library hours (bilingually, see below). It could be useful beyond that.

Based on code posted a long time ago by Andrew Darby to code4lib mailing list (then [written up in its journal](http://journal.code4lib.org/articles/46)) which used [Google Calendar](http://calendar.google.com). The [Calendar API](http://developers.google.com/google-apps/calendar/) (or was it the [Zend-based PHP library](http://framework.zend.com/manual/1.12/en/zend.gdata.html) that called it?) was returning incorrect responses to some queries, so we looked for alternatives. The code was completely rewritten and then refactored along with the database schema, so none of Andrew's implementation is intact AFAICS.

Though it fits our current purposes, there's plenty more that can be done with this. They shall be logged as [tickets](issues).

Reference implementation
---------------------
Feel free to use [our implementation](http://library2.lincoln.ac.nz/hours) as your reference, but the committed version is de-branded and attempts to be plain/vanilla. The HTML metadata element _generator_ shows the version deployed there.

Assumptions
------------
These probably won't change anytime soon. If they don't suit then this code might not be for you:

* **One opening per day.** No lunch breaks, sorry.
* **_index.php_ is the default document.** That's easily fixed in your web server config if you don't like it.

These are current assumptions we plan to make go away:

* **Hours are loaded in ahead of time.** Just how we roll right now. If you want to publish data from days past, you missed your chance. You'll get gaps. Pretty sure it's an easy fix. Just keep up for now.
* **Bilingual in _en_ and _mi_.** Andrew's calendar was hardcoded English. We wanted it bilingual with [Māori](http://en.wikipedia.org/wiki/M%C4%81ori_language) (for [Māori Language Week](http://www.nzhistory.net.nz/culture/maori-language-week) originally), so we coded that in. The words are from a lookup array and the hash index is the language code, so with a large simple edit, we could greatly extend the number of language configurations possible. We won't need a second language, we won't need English as the first one, and maybe we can be even more multilingual. As is, it's probably only useful to New Zealand libraries.
* **MySQL and PHP.** We'll be more specific about that in the installation section.

How to install and make it useful
--------------
So if all of the above is acceptable, here's how you do it.

**Requirements**

We run it on IIS with MySQL and PHP 5._x_ where _x_ < 2. Importantly, the DateTime object isn't required. You will need to set up _index.php_ as a default document for your directory in your web server. It probably already is. Nothing else I can think of is non-standard.

**Installing**

Copy the files within the web server path you want.

Create a MySQL database that the web server can see (or use an existing one), select it, and create a single table using this SQL. Switch the table name if you don't like "libhrs":

```SQL
CREATE TABLE libhrs (
  id int(11) NOT NULL AUTO_INCREMENT, -- can be dropped, but is handy for debugging
  day date NOT NULL,
  opens datetime DEFAULT NULL,
  closes datetime DEFAULT NULL,
  PRIMARY KEY (id), -- droppable, see above
  UNIQUE KEY day_UNIQUE (day)
);
```

(The repository file [csv_load.php](csv_load.php) has this code at the top in a PHP block comment.)

Next configure your database for a load. (See below for migrating existing calendar data from Andrew Darby's orginal application.)

1. Rename the file [database.EXAMPLE.php](lib/config/database.EXAMPLE.php) to database.php and edit the database settings there.
1. Rename the file [settings.EXAMPLE.php](lib/config/settings.EXAMPLE.php) to settings.php and edit at least `$timezone` and `$data_file` if you want to put it somewhere else.

If you were successful, you should be able to see a working HTML display in your browser at the root of the web server path where you placed it. There won't be any data in it though.

**Loading data**

Now it's time to create some opening times in a CSV file or spreadsheet. I suggest taking the file [calendar.xlsx](calendar.xlsx), editing that in your favourite spreadsheet application (Open/LibreOffice should load it fine) because it will enforce cell datatypes, and then exporting as CSV. Alternatively, just edit the [calendar.csv](calendar.csv) file directly (and stick to the date and time formats already there).

> _For the moment, open hours data after the present day are deleted when the data is loaded. There'll be a gap in your calendar if you delete these rows in your spreadsheet or CSV file. Don't delete further back than today's date._

* For continuous periods of the same opening hours, just populate the _Period start_ and _Period end_ columns. In the _Recurrence_ column, list the days of the week the hours apply to, as numbers. 1 is Monday through to 7 is Sunday. Don't delimit multiple numbers. For example, "5" is every Friday and "123" is Monday to Wednesday.
* For a single day, just populate the _Period start_ column and leave _Period end_ blank.
* Populate the opening hours in the _Opens_ and _Closes_ columns. _For closed days_, leave both of those columns blank.
* Use the _Notes_ column for your own convenience. It goes nowhere else. _In future, it might be used to show users extra information, e.g. in a tooltip ("Summer holidays" or "Labour Day")._
* You can add exceptions to recurring period hours by creating another row any time after the original period's row. So for example, add a public holiday as you would any other day _after_ the period in which it occurs. The [example in the repository](calendar.csv) contains [one such example for October 28, 2013](calendar.csv#l19) (exception to [line 12](calendar.csv#l13)).

If you did this on the [.xlsx](calendar.xlsx) file, export it as CSV.

> _If that's a bit tricky to follow, load the example data as a trial, but edit it first to make sure it's in the future (to work around a current bug/feature). The easiest way to do this would be to change the year on all dates to a future one. If you do that, you might need to edit `$populate_months` in [settings.php](lib/config/settings.EXAMPLE.php) to make it load that far into the future._

Now in your web browser, open [csv_load.php](csv_load.php) under the path you placed your files. If you did everything above right, you should get happy output. If not, check your database settings, file names (did you rename the settings files?), and CSV file. Try try again.

If you successfully loaded data, you should now be able to see it in HTML in your browser at the root of the web server path where you placed it. Remember that if you used future-dated test data you might need to page forward in your browser to see it. By default, the current month is shown.

**Customising**

Unfortunately content is still quite mixed with logic as far as files go. In order of importance, these are probably the files you want to edit after a successful load:

* [lib/config/settings.php](lib/config/settings.EXAMPLE.php)
* [lib/templates/top.php](lib/templates/top.php)
* [lib/templates/bottom.php](lib/templates/bottom.php)
* [styles/hours.css](styles/hours.css)

You probably don't want to leave files on the web server so that your data can be re-imported by anyone on the internet. It wouldn't have any real effect, but it's not the safest of practices the way it's set up currently. (It's not [safe](http://en.wikipedia.org/wiki/Hypertext_Transfer_Protocol#Safe_methods) in the [RESTful](http://en.wikipedia.org/wiki/Representational_state_transfer) sense.) Get rid of (or rename obscurely) either [csv_load.php](csv_load.php) or [calendar.csv](calendar.csv) to make such attempts fail.

**Migrating**

To load data from the database structure used in [Andrew Darby's script](http://journal.code4lib.org/articles/46), there is a piece of SQL you can run included in the file [lib/config/database.EXAMPLE.php](lib/config/database.EXAMPLE.php). It worked for us.

Issues
----------
Please report or peruse any issues, or suggest enhancements at the Github repository master:

<http://github.com/LincolnUniLTL/calibr/issues>

The project's home is at <http://github.com/LincolnUniLTL/calibr> and some links in this README are relative to that.