const fs = require('fs');
const path = require('path');
const glob = require('glob');

const projectRoot = path.join(__dirname, '..'); // Parent directory of 'bin'
const buildPath = path.join(projectRoot, 'build');

// Load package.json
const packageJSON = require(path.join(projectRoot, 'package.json'));

// Ensure there's a 'build' directory
if (fs.existsSync(buildPath)) {
    fs.rmSync(buildPath, { recursive: true });
    fs.mkdirSync(buildPath);
} else {
    fs.mkdirSync(buildPath);
}

// Function to copy files
function copyFiles(source, target) {
    let targetFile = target;

    // Check if source is a directory
    if (fs.lstatSync(source).isDirectory()) {
        if (!fs.existsSync(targetFile)) {
            fs.mkdirSync(targetFile, { recursive: true });
        }
    } else {
        // If target is a directory, append the source file's basename to the target path
        if (fs.existsSync(target) && fs.lstatSync(target).isDirectory()) {
            targetFile = path.join(target, path.basename(source));
        }
        fs.copyFileSync(source, targetFile);
    }
}


// Process each pattern in the 'files' array
packageJSON.files.forEach(pattern => {
    // Use glob to resolve the file paths, relative to the project root
    const files = glob.sync(path.join(projectRoot, pattern));

    files.forEach(file => {
        // Define the destination path
        const dest = path.join(buildPath, path.relative(projectRoot, file));
        const destDir = path.dirname(dest);

        // Ensure the directory exists
        if (!fs.existsSync(destDir)) {
            fs.mkdirSync(destDir, { recursive: true });
        }

        // Copy the file
        copyFiles(file, dest);
    });
});