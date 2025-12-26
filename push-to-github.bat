@echo off
echo ================================================
echo HUBIZZ - Push to GitHub Script
echo ================================================
echo.
echo This script will help you push Hubizz to GitHub
echo.
echo BEFORE RUNNING:
echo 1. Create a GitHub repository at: https://github.com/new
echo 2. Copy the repository URL
echo.
echo ================================================
echo.

set /p REPO_URL="Enter your GitHub repository URL: "

if "%REPO_URL%"=="" (
    echo ERROR: No URL provided
    pause
    exit /b 1
)

echo.
echo ================================================
echo Step 1: Checking Git status...
echo ================================================
git status

echo.
echo ================================================
echo Step 2: Adding all files...
echo ================================================
git add .

echo.
echo ================================================
echo Step 3: Committing changes...
echo ================================================
git commit -m "chore: prepare for v1.0.0 release - audit complete, security hardened"

echo.
echo ================================================
echo Step 4: Renaming branch to 'main'...
echo ================================================
git branch -M main

echo.
echo ================================================
echo Step 5: Adding remote repository...
echo ================================================
git remote add origin %REPO_URL%

echo.
echo ================================================
echo Step 6: Pushing to GitHub...
echo ================================================
git push -u origin main

echo.
echo ================================================
echo SUCCESS! Hubizz pushed to GitHub
echo ================================================
echo.
echo Next steps:
echo 1. Visit your repository on GitHub
echo 2. Verify all files are present
echo 3. Check that .env is NOT there
echo 4. Set repository description and topics
echo.
echo HUBIZZ - Where Content Ignites!
echo ================================================
pause
