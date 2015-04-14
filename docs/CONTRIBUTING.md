# Contributing for Cmless project

Want to contribute to our project? 
Please review this document in order to make the process easier.
This will help the maintainer to ensure the quality of the code and make communication simpler.

## Git Setup

1. Go to the [project's Github](https://github.com/BorealHub/cmless) and **fork your own copy**
2. Clone your fork
3. Open a terminal in the git and enter the following commands to set the upstream: 
```
git remote add upstream git@github.com:BorealHub/cmless.git
git remote set-url --push upstream no_push
git config branch.master.remote upstream
git config branch.master.merge refs/heads/master
```

Now you can **git pull** from the master but **cannot push** to **upstream**, 
so you are obliged to create a new branch for each new feature. When you will push your
feature branch to **origin** (your own fork), you'll be able to do a **pull-request** to **BorealHub/cmless**
from your/our repository on Github. The following block contains some basic commands that can be useful for beginners:

```
// Checkout on a new branch from master
git checkout master
git checkout -b newFeatureBranch

// How to squash : revert to the last commit from the master before your feature...
git reset --soft theLastCommitFromMasterHash
git add -A
git commit
git push --force origin myBranch
```

## Guidelines

- Following an appropriate Git setup ; Never push upstream and use pull-requests
- Code readability is important and try to make it uniform with the rest. A reviewer may ask for corrections or even reject a PR which does not follow some quality standards.
- Ask a maintainer to review your pull-request, e.g : "**@BorealHub/maintainer** could you review this PR please?" (The "@" is important to notify them)
- Squash your PR when the maintainer tells you (not before) so he can merge it.
- When doing the final squash : Do a commit message following this general form...
```
A summary line of what it is

Details of the PR content :
- Modified Foo
- Implemented a new Bar
```

Some rules may be added soon, for e.g : **"Do unit tests"**
