#!/bin/bash
# delete all branches except main and production
git branch | grep -v "main\|production" | xargs git branch -D;