#!/bin/bash
cd /var/www/your-project && docker-compose down --remove-orphans && docker-compose up -d && sleep 15 && echo "Testing..." && curl http://localhost/health && curl http://localhost/api/categories
