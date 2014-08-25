# TYPO3 Migrator

## A TYPO3 Extension for SQL Migrations

Today TYPO3 projects are mostly developed and deployed using git. This extension can help you to work with SQL changes in this environment.
Apply SQL changes on all developer instances or even deploy changes to your Live System.

## What it does

The migrator just executes numbered sql files (`001.sql`, `002.sql`, etc) that you place in a certain directory. Once the migrator is called it checks for new .sql files and executes them in the right order.

So if you want to distribute a SQL Command (e.g. an INSERT statement for a new record) accross your installations, just create a file with a higher number than the existing ones and push it into your repository. Once others pull it and execute the migrator they will have your changes applied!

## Setup

* Create a folder where you want to have your SQL files. If it is inside your web root, protect it with a *.htaccess* file with the following contents: `deny from all`.
* Install this Extension using the Extension Manager.
* Configure it in the Extension Manager.
  * *Path to the folder with SQL files*: Relative path to the folder you just created.
  * *Path to the mysql binary*: Adjust to the mysql binary on your machine.

## Invoke

The Migrator can be invoked in two ways:

### Command Line

$php cli_dispatch.phpsh extbase migration:migratesqlfiles

### Scheduler Task

Create a Scheduler Task of the class "Extbase Command Controller Task" and choose "Migrator Migration: migrateSqlFiles" as command. You can invoke the task regularly by cronjob or just by hand when you need it.

## Troubleshooting

If the execution of an SQL file fails, the counter of the last last executed file is not increased and the file will be executed again on the next run. Notice that it already might have done changes to your database before it crashed.

If you want to see/change the internal pointer of the last executed SQL file, you'll find it in the table `sys_registry` with the namespace and key `AppZap\Migrator` / `lastExecutedVersion`.

### Can I omit migration numbers?

Yes, after `003.sql` the next file can be `005.sql`. Be aware once `005.sql` is executed and you add `004.sql` afterwards, `004.sql` will never be executed.

### Can I use timestamps as migration numbers?

Yes.

### Can I start at 0?

No, at the moment the first file must be `001.sql` or higher.

### Do I need the leading zeros?

No, you can also use `1.sql` and so on.

### Can I undo a migration?

No, the migrator only knows one direction. You'll need to do it manually.