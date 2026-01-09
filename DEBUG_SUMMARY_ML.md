
# Debug Summary: Mercado Libre Integration & Syntax Fixes

## Problem
1. **Critical Syntax Error**: `ParseError: syntax error, unexpected token ",", expecting ";"` in `PlanResource.php` line 388 (server-side).
2. **Mercado Libre Validation Errors**:
   - `body.invalid_fields` (Title contains illegal characters or repetition).
   - `body.required_fields` (Missing `family_name` attribute, inconsistent case sensitivity requirements).

## Diagnosis
- The syntax error was caused by an accidental deletion of the strict `Step::make('Dados Básicos')->schema([...])` wrapper around the `title` input field during a previous edit.
- This missing wrapper caused an orphan `])` bracket later in the file (line 233) to prematurely close the `->steps([])` array.
- Consequently, subsequent `Step::make` calls were interpreted as standalone statements, and the outer `->actions([])` array closure became misaligned, baffling the PHP parser.
- The Mercado Libre API for "Software" category (`MLB1728`) has strict and sometimes contradictory requirements:
  - Requires `FAMILY_NAME` in `attributes` (documented).
  - Also requires `family_name` (lowercase) in `attributes` (undocumented edge case for some categories).
  - Sometimes requires `family_name` at the root of the JSON body (legacy behavior for non-catalog items).

## Solution
1. **Fixed PHP Structure**:
   - Restored the missing `Step::make` and `schema` definition in `PlanResource.php`.
   - Verified bracket balancing.
   - Restored the comma separator in the `actions` array.

2. **Robust Attribute Injection (The "Triple-Check" Strategy)**:
   - Applied to both `PlanResource.php` and `DownloadResource.php`.
   - **Check 1**: Ensure `FAMILY_NAME` (uppercase) is in `attributes`.
   - **Check 2**: Ensure `family_name` (lowercase) is in `attributes`.
   - **Check 3**: Inject `$body['family_name'] = 'Software'` at the root level of the request.

3. **Title Sanitization**:
   - Implemented logic to remove forbidden characters: `=`, `*`, `+`.
   - Implemented smart deduplication: If the software name is already in the plan name, do not repeat it.
   - Truncated title to 60 characters.

## Deployment Instructions
1. **Pull Changes**:
   ```bash
   cd /code
   git pull
   ```
2. **Clear Caches**:
   ```bash
   php artisan optimize:clear
   ```
3. **Verify**:
   - Try to publish a Plan or Download again.
   - If it fails, check the "Notifications" bell in the admin panel. The error message will now contain the full JSON sent and the raw API response for easy debugging.
