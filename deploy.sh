#!/bin/bash

# Hi5ve MarketPlace Deployment Script
# For Shared Hosting Deployment

set -e  # Exit on any error

# Configuration
REMOTE_HOST="your-domain.com"
REMOTE_USER="your-username"
REMOTE_PATH="/public_html"
LOCAL_PATH="."
BACKUP_DIR="backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 Starting Hi5ve MarketPlace Deployment${NC}"

# Function to print colored output
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if required tools are installed
check_dependencies() {
    print_status "Checking dependencies..."
    
    if ! command -v rsync &> /dev/null; then
        print_error "rsync is required but not installed"
        exit 1
    fi
    
    if ! command -v ssh &> /dev/null; then
        print_error "ssh is required but not installed"
        exit 1
    fi
    
    print_status "Dependencies check passed"
}

# Create backup of current deployment
create_backup() {
    print_status "Creating backup of current deployment..."
    
    ssh $REMOTE_USER@$REMOTE_HOST "
        cd $REMOTE_PATH
        if [ -d 'current' ]; then
            mkdir -p $BACKUP_DIR
            tar -czf $BACKUP_DIR/backup_$TIMESTAMP.tar.gz current/
            echo 'Backup created: $BACKUP_DIR/backup_$TIMESTAMP.tar.gz'
        fi
    "
}

# Prepare local files for deployment
prepare_deployment() {
    print_status "Preparing files for deployment..."
    
    # Create temporary deployment directory
    rm -rf temp_deploy
    mkdir temp_deploy
    
    # Copy files excluding development files
    rsync -av \
        --exclude='.git' \
        --exclude='.github' \
        --exclude='node_modules' \
        --exclude='tests' \
        --exclude='docs' \
        --exclude='*.md' \
        --exclude='debug_*.php' \
        --exclude='test_*.php' \
        --exclude='check_*.php' \
        --exclude='classes/SimpleFileUpload.php' \
        --exclude='temp_deploy' \
        $LOCAL_PATH/ temp_deploy/
    
    # Copy production config
    if [ -f "config/production.php" ]; then
        cp config/production.php temp_deploy/config/config.php
        print_status "Production config copied"
    else
        print_warning "Production config not found, using default"
    fi
    
    # Set proper permissions
    find temp_deploy -type f -name "*.php" -exec chmod 644 {} \;
    find temp_deploy -type d -exec chmod 755 {} \;
    chmod 644 temp_deploy/.htaccess
    
    print_status "Files prepared for deployment"
}

# Deploy files to server
deploy_files() {
    print_status "Deploying files to server..."
    
    # Upload files
    rsync -avz \
        --delete \
        --exclude='uploads/' \
        --exclude='logs/' \
        --exclude='cache/' \
        temp_deploy/ $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/
    
    print_status "Files uploaded successfully"
}

# Set proper permissions on server
set_permissions() {
    print_status "Setting proper permissions..."
    
    ssh $REMOTE_USER@$REMOTE_HOST "
        cd $REMOTE_PATH
        
        # Set general permissions
        find . -type f -name '*.php' -exec chmod 644 {} \;
        find . -type d -exec chmod 755 {} \;
        
        # Set upload directory permissions
        if [ ! -d 'uploads' ]; then
            mkdir -p uploads/{products,categories,blog,pages,settings,temp}
        fi
        chmod -R 777 uploads/
        
        # Set log directory permissions
        if [ ! -d 'logs' ]; then
            mkdir -p logs
        fi
        chmod 755 logs/
        
        # Set cache directory permissions
        if [ ! -d 'cache' ]; then
            mkdir -p cache
        fi
        chmod 755 cache/
        
        # Secure sensitive files
        chmod 644 .htaccess
        chmod -R 755 config/
        
        echo 'Permissions set successfully'
    "
}

# Run database migrations if needed
run_migrations() {
    print_status "Running database migrations..."
    
    ssh $REMOTE_USER@$REMOTE_HOST "
        cd $REMOTE_PATH
        
        # Check if migration script exists
        if [ -f 'migrate.php' ]; then
            php migrate.php
            echo 'Database migrations completed'
        else
            echo 'No migration script found, skipping'
        fi
    "
}

# Clear cache and optimize
optimize_deployment() {
    print_status "Optimizing deployment..."
    
    ssh $REMOTE_USER@$REMOTE_HOST "
        cd $REMOTE_PATH
        
        # Clear cache
        if [ -d 'cache' ]; then
            rm -rf cache/*
            echo 'Cache cleared'
        fi
        
        # Optimize if composer is available
        if [ -f 'composer.phar' ]; then
            php composer.phar dump-autoload --optimize --no-dev
            echo 'Autoloader optimized'
        fi
    "
}

# Health check
health_check() {
    print_status "Performing health check..."
    
    # Wait a moment for deployment to settle
    sleep 5
    
    # Check if site responds
    if curl -f -s "https://$REMOTE_HOST" > /dev/null; then
        print_status "Health check passed - Site is responding"
    else
        print_error "Health check failed - Site may not be responding"
        return 1
    fi
}

# Rollback function
rollback() {
    print_warning "Rolling back to previous version..."
    
    ssh $REMOTE_USER@$REMOTE_HOST "
        cd $REMOTE_PATH
        
        # Find latest backup
        LATEST_BACKUP=\$(ls -t $BACKUP_DIR/backup_*.tar.gz 2>/dev/null | head -1)
        
        if [ -n \"\$LATEST_BACKUP\" ]; then
            echo 'Rolling back to: \$LATEST_BACKUP'
            
            # Remove current deployment
            rm -rf current_failed
            mv current current_failed
            
            # Restore backup
            tar -xzf \$LATEST_BACKUP
            
            echo 'Rollback completed'
        else
            echo 'No backup found for rollback'
        fi
    "
}

# Cleanup
cleanup() {
    print_status "Cleaning up..."
    rm -rf temp_deploy
    print_status "Cleanup completed"
}

# Main deployment process
main() {
    echo -e "${GREEN}Starting deployment to $REMOTE_HOST${NC}"
    
    check_dependencies
    create_backup
    prepare_deployment
    deploy_files
    set_permissions
    run_migrations
    optimize_deployment
    
    if health_check; then
        print_status "🎉 Deployment completed successfully!"
        cleanup
    else
        print_error "Deployment failed health check"
        read -p "Do you want to rollback? (y/n): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            rollback
        fi
        cleanup
        exit 1
    fi
}

# Handle script arguments
case "${1:-deploy}" in
    "deploy")
        main
        ;;
    "rollback")
        rollback
        ;;
    "health-check")
        health_check
        ;;
    *)
        echo "Usage: $0 {deploy|rollback|health-check}"
        exit 1
        ;;
esac 