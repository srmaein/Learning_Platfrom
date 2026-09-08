@echo off
setlocal EnableExtensions EnableDelayedExpansion

title GitHub Project Uploader

cls
echo ========================================================
echo        WINDOWS GITHUB PROJECT UPLOADER
echo ========================================================
echo.

:: ---------------------------------------------------------
:: Step 1: Detect Project Directory
:: ---------------------------------------------------------
set "PROJECT_DIR=%~dp0"
if "%PROJECT_DIR:~-1%"=="\" set "PROJECT_DIR=%PROJECT_DIR:~0,-1%"

echo [1/7] Project Directory Detected:
echo       "%PROJECT_DIR%"
echo.

cd /d "%PROJECT_DIR%"
if !errorlevel! neq 0 (
    echo ERROR: Failed to navigate to project directory: "%PROJECT_DIR%"
    goto :FAIL
)

:: ---------------------------------------------------------
:: Step 2: Check Git Installation & Auto-Resolve Path
:: ---------------------------------------------------------
echo [2/7] Checking Git installation...

git --version >nul 2>&1
if errorlevel 1 (
    if exist "C:\Program Files\Git\cmd\git.exe" set "PATH=C:\Program Files\Git\cmd;!PATH!"
    if exist "C:\Program Files (x86)\Git\cmd\git.exe" set "PATH=C:\Program Files (x86)\Git\cmd;!PATH!"
    if exist "%LocalAppData%\Programs\Git\cmd\git.exe" set "PATH=%LocalAppData%\Programs\Git\cmd;!PATH!"
    if exist "%ProgramFiles%\Git\cmd\git.exe" set "PATH=%ProgramFiles%\Git\cmd;!PATH!"
)

git --version >nul 2>&1
if errorlevel 1 goto :GIT_NOT_FOUND

for /f "tokens=*" %%V in ('git --version') do set "GIT_VER=%%V"
echo       Detected: %GIT_VER%
echo.
goto :GIT_FOUND

:GIT_NOT_FOUND
echo.
echo ========================================================
echo ERROR: Git is not installed or not available in PATH.
echo Please install Git for Windows (https://git-scm.com/)
echo and try again.
echo ========================================================
goto :FAIL

:GIT_FOUND

:: ---------------------------------------------------------
:: Step 3: Check Git User Configuration
:: ---------------------------------------------------------
set "GIT_USER="
set "GIT_EMAIL="
for /f "tokens=*" %%U in ('git config user.name 2^>nul') do set "GIT_USER=%%U"
for /f "tokens=*" %%E in ('git config user.email 2^>nul') do set "GIT_EMAIL=%%E"

if "!GIT_USER!"=="" (
    echo WARNING: Git user.name is not configured.
    set /p "GIT_USER=Enter your Git user name: "
    if "!GIT_USER!"=="" (
        echo ERROR: Git user name cannot be empty.
        goto :FAIL
    )
    git config --global user.name "!GIT_USER!"
)

if "!GIT_EMAIL!"=="" (
    echo WARNING: Git user.email is not configured.
    set /p "GIT_EMAIL=Enter your Git email: "
    if "!GIT_EMAIL!"=="" (
        echo ERROR: Git email cannot be empty.
        goto :FAIL
    )
    git config --global user.email "!GIT_EMAIL!"
)

:: ---------------------------------------------------------
:: Step 4: Prompt and Validate GitHub Repository URL
:: ---------------------------------------------------------
:PROMPT_URL
echo --------------------------------------------------------
echo Enter GitHub repository URL (HTTPS or SSH):
echo Example: https://github.com/username/repository.git
echo      or: git@github.com:username/repository.git
echo --------------------------------------------------------
set "REPO_URL="
set /p "REPO_URL=> "

if not defined REPO_URL (
    echo.
    echo ERROR: Repository URL cannot be empty. Please try again.
    echo.
    goto :PROMPT_URL
)

:: Clean quotes and whitespace from REPO_URL
for /f "tokens=1" %%A in ("!REPO_URL!") do set "REPO_URL=%%A"
set "REPO_URL=!REPO_URL:"=!"

if "!REPO_URL!"=="" (
    echo.
    echo ERROR: Repository URL cannot be empty. Please try again.
    echo.
    goto :PROMPT_URL
)

:: Remove trailing slash if present
if "!REPO_URL:~-1!"=="/" set "REPO_URL=!REPO_URL:~0,-1!"

:: URL Validation
set "VALID_URL=0"
if "!REPO_URL:~0,19!"=="https://github.com/" set "VALID_URL=1"
if "!REPO_URL:~0,18!"=="http://github.com/" set "VALID_URL=1"
if "!REPO_URL:~0,15!"=="git@github.com:" set "VALID_URL=1"

if "!VALID_URL!"=="0" (
    echo.
    echo ERROR: Invalid GitHub repository URL.
    echo URL must start with 'https://github.com/' or 'git@github.com:'.
    echo Entered URL: "!REPO_URL!"
    echo.
    goto :PROMPT_URL
)

:: Normalize HTTPS URL to include .git extension if missing
if "!REPO_URL:~0,19!"=="https://github.com/" (
    if /i not "!REPO_URL:~-4!"==".git" (
        set "REPO_URL=!REPO_URL!.git"
        echo Automatically normalized HTTPS URL: "!REPO_URL!"
    )
)

echo.
echo [3/7] Repository URL validated: !REPO_URL!
echo.

:: ---------------------------------------------------------
:: Step 5: Git Repository & Remote Setup
:: ---------------------------------------------------------
echo [4/7] Checking Git repository state...
if not exist "%PROJECT_DIR%\.git" (
    echo       Initializing new Git repository...
    git init
    if errorlevel 1 (
        echo ERROR: Failed to initialize Git repository.
        goto :FAIL
    )
) else (
    echo       Existing local Git repository detected.
)

:: Check current remote 'origin'
set "EXISTING_ORIGIN="
for /f "tokens=1" %%O in ('git remote get-url origin 2^>nul') do set "EXISTING_ORIGIN=%%O"

if not "!EXISTING_ORIGIN!"=="" (
    echo.
    echo --------------------------------------------------------
    echo Existing Git remote detected:
    echo origin = !EXISTING_ORIGIN!
    echo --------------------------------------------------------
    if /i "!EXISTING_ORIGIN!"=="!REPO_URL!" (
        echo Remote URL already matches target repository.
    ) else (
        echo Do you want to replace existing origin with the new URL?
        set /p "REPLACE_REMOTE=[Y/N]?: "
        if /i "!REPLACE_REMOTE!"=="Y" (
            git remote set-url origin "!REPO_URL!"
            if errorlevel 1 (
                echo ERROR: Failed to update remote URL.
                goto :FAIL
            )
            echo Remote URL updated to: !REPO_URL!
        ) else (
            echo Operation cancelled by user to avoid remote mismatch.
            goto :FAIL
        )
    )
) else (
    git remote add origin "!REPO_URL!"
    if errorlevel 1 (
        echo ERROR: Failed to add remote origin.
        goto :FAIL
    )
    echo Remote origin added: !REPO_URL!
)
echo.

:: ---------------------------------------------------------
:: Step 6: Determine Active Branch
:: ---------------------------------------------------------
set "CURRENT_BRANCH="
for /f "tokens=*" %%B in ('git symbolic-ref --short HEAD 2^>nul') do set "CURRENT_BRANCH=%%B"

if "!CURRENT_BRANCH!"=="" (
    set "CURRENT_BRANCH=main"
    git branch -M main >nul 2>&1
)
echo Active branch set/detected: !CURRENT_BRANCH!
echo.

:: ---------------------------------------------------------
:: Step 7: Stage Files
:: ---------------------------------------------------------
echo [5/7] Staging all Git-trackable project files (git add -A)...
git add -A
if errorlevel 1 (
    echo ERROR: Failed to stage project files.
    goto :FAIL
)

echo.
echo Project Staging Summary:
echo --------------------------------------------------------
git status --short
echo --------------------------------------------------------
echo.

:: Check if changes exist to commit
set "HAS_CHANGES=0"
for /f "tokens=*" %%S in ('git status --porcelain 2^>nul') do set "HAS_CHANGES=1"

:: ---------------------------------------------------------
:: Step 8: Commit Changes
:: ---------------------------------------------------------
echo [6/7] Creating commit...
if "!HAS_CHANGES!"=="0" (
    echo No new changes detected to commit.
    goto :SKIP_COMMIT
)

:: Determine ISO Date for default commit message
set "TODAY="
for /f "tokens=2 delims==" %%D in ('wmic os get localdatetime 2^>nul') do (
    set "DT=%%D"
    if not "!DT!"=="" set "TODAY=!DT:~0,4!-!DT:~4,2!-!DT:~6,2!"
)
if "!TODAY!"=="" set "TODAY=%DATE%"

set "DEFAULT_COMMIT_MSG=Update project files - !TODAY!"

echo.
echo Enter commit message (Press ENTER to use default: "!DEFAULT_COMMIT_MSG!"):
set "USER_COMMIT_MSG="
set /p "USER_COMMIT_MSG=> "
if defined USER_COMMIT_MSG set "USER_COMMIT_MSG=!USER_COMMIT_MSG:"=!"

if "!USER_COMMIT_MSG!"=="" (
    set "COMMIT_MSG=!DEFAULT_COMMIT_MSG!"
) else (
    set "COMMIT_MSG=!USER_COMMIT_MSG!"
)

git commit -m "!COMMIT_MSG!"
if errorlevel 1 (
    echo ERROR: Git commit failed.
    goto :FAIL
)
echo Commit created successfully: "!COMMIT_MSG!"

:SKIP_COMMIT
echo.

:: ---------------------------------------------------------
:: Step 9: Push to GitHub with Retry Option
:: ---------------------------------------------------------
echo [7/7] Uploading project to GitHub (!CURRENT_BRANCH!)...
set "ATTEMPT=1"
set "MAX_ATTEMPTS=3"

:PUSH_LOOP
echo.
echo Attempt !ATTEMPT! of !MAX_ATTEMPTS!: Executing git push -u origin !CURRENT_BRANCH!...
git push -u origin !CURRENT_BRANCH!

if not errorlevel 1 goto :PUSH_SUCCESS

echo.
echo ========================================================
echo ERROR: Git push failed!
echo ========================================================
echo Possible causes:
echo 1. Remote repository contains commits not present locally.
echo 2. GitHub authentication required (Git Credential Manager / SSH key).
echo 3. Network connection issue or invalid repository permissions.
echo.
echo NOTE: Force push (--force) is STRICTLY DISABLED for data safety.
echo ========================================================

if !ATTEMPT! LSS !MAX_ATTEMPTS! (
    set /a ATTEMPT+=1
    echo.
    echo Would you like to retry the push?
    set /p "RETRY_PUSH=[Y/N]?: "
    if /i "!RETRY_PUSH!"=="Y" goto :PUSH_LOOP
)

goto :PUSH_FAIL

:PUSH_SUCCESS
echo.
echo Verifying final repository status...
git status
echo.
echo ========================================================
echo                UPLOAD SUCCESSFUL!
echo ========================================================
echo Repository: !REPO_URL!
echo Branch:     !CURRENT_BRANCH!
echo Location:   %PROJECT_DIR%
echo Status:     All Git-trackable files uploaded successfully.
echo ========================================================
echo.
pause
exit /b 0

:PUSH_FAIL
echo.
echo ========================================================
echo                   UPLOAD FAILED
echo ========================================================
echo Please verify your GitHub authentication, repository permissions,
echo or network connection and try again.
echo ========================================================
echo.
pause
exit /b 1

:FAIL
echo.
echo ========================================================
echo                 OPERATION CANCELLED / FAILED
echo ========================================================
echo.
pause
exit /b 1
